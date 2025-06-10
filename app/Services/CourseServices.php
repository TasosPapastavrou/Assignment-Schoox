<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Tag;
use Illuminate\Support\Collection;
use App\Repositories\CourseRepositoryInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use App\Events\CourseChange;

class CourseServices implements CourseRepositoryInterface
{
    private $statuses = [
            0 => ['status' => "error", "message"=> "", "data" => []],
            1 => ['status' => "success", "message"=> "", "data" => []],
    ];

    public function getAllCourses(Request $request)
    {

        $response = [];
        $httpReqStatus = 200;

        $paginateValidation = Validator::make($request->all(), [
            'per_page' => 'required|numeric|gt:0',
            'page' => 'required|numeric|gt:0'
        ]);

        if ($paginateValidation->fails()) {
            $response = $this->statuses[0];
            $httpReqStatus = 422;
            $response["message"] = $paginateValidation->errors();
            return ['response' => $response, "http_status" => $httpReqStatus];
        }

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page');
        $response = $this->statuses[1];

        $response['data'] = Cache::remember("get_courses_per_page_{$perPage}_page_{$page}", now()->addMinutes(60), function () use ($request) {

            $perPage = $request->input('per_page', 10) ?? '';
            $courses = Course::with('tags')->paginate($perPage);

            return [
                'courses' => $courses->items(),
                'pagination' => [
                    'total' => $courses->total(),
                    'count' => $courses->count(),
                    'per_page' => $courses->perPage(),
                    'current_page' => $courses->currentPage(),
                    'total_pages' => $courses->lastPage(),
                ]
            ]; 
        });

        return ['response' => $response, "http_status" => $httpReqStatus];
    }

    public function getCourseById(int $id){

        $response = [];
        $httpReqStatus = '';
        $course = Cache::remember("get_course_{$id}", now()->addMinutes(60), function () use ($id) {
                    return Course::find($id); 
                  });

        if($course){
            $httpReqStatus = 200;
            $response = $this->statuses[1];
            $response["data"] = $course;
        }else{
            $httpReqStatus = 404; 
            $response = $this->statuses[0];
            $response["message"] = "We couldn't find a course with ID {$id}.";
        }

        return ['response' => $response, "http_status" => $httpReqStatus];   
    }

    public function storeCourse(Request $request){

        $response = [];
        $httpReqStatus = 200;
        $tags = $request->input('tags') ?? [];
        $tagsId = [];
        $userId = $request->user()->id; 
        $haveNewTags = false;

        $courceDataValidation = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'status' => 'nullable|in:published,draft,archived',
            'is_premium' => 'nullable|boolean',
        ]);

        if ($courceDataValidation->fails()) {
            $response = $this->statuses[0];
            $httpReqStatus = 422;
            $response["message"] = $courceDataValidation->errors();
            return ['response' => $response, "http_status" => $httpReqStatus];
        } 

        $dataOfCourse = $request->only('title', 'description','status', 'is_premium');
        $dataOfCourse['user_id'] = $userId;
        $course = Course::create($dataOfCourse);

        if($tags)
            $haveNewTags = $this->storeTags($tags);  

        if($haveNewTags)
            $tagsId = $this->getNewCourseTags($tags,$course); 

        if($tagsId)
            $course->tags()->attach($tagsId);

        $course->load('tags');
        $response = $this->statuses[1];
        $response["data"] = $course;
        $response["message"] = "Course created successfully.";
        event(new CourseChange());

        return ['response' => $response, "http_status" => $httpReqStatus];
        
    }

    public function storeTags($tagNames){
        
        $haveNewTags = false;
        $existingTags = Tag::whereIn('name', $tagNames)->pluck('name')->all();

        $missingTags = collect($tagNames)
            ->diff($existingTags)
            ->values();

        if($missingTags){

            $now = now();

            $newTags = collect($missingTags)->map(fn($tag) => [
                'name' => $tag,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            Tag::insert($newTags);

            $haveNewTags = true;
        }

        return $haveNewTags;
    }

    public function getNewCourseTags($tagNames, $course){

        $newCourseTags = [];
        $courseTags = collect($course->tags->pluck('name'));

        if($courseTags){
            $newCourseTags = collect($tagNames)->filter(function ($value, $key) use ($courseTags) {
                return !$courseTags->contains($value);
            });
        }else{
            $newCourseTags = $tagNames;
        }

        $newCourseTagIds = Tag::whereIn('name', $newCourseTags)->pluck('id')->all();
        return $newCourseTagIds;
    }


    public function courseDelete(int $id){

        $response = [];
        $httpReqStatus = 200;
        $course = Course::find($id);

        if ($course) {
            $course->delete();
            $httpReqStatus = 200;
            $response = $this->statuses[1];
            $response["message"] = "Course deleted successfully";
            $response["data"] = $course;
            event(new CourseChange());
        }else{

            $response = $this->statuses[0];
            $httpReqStatus = 404;
            $response["message"] = "Course with ID ".$id." not found.";
        }

        return ['response' => $response, "http_status" => $httpReqStatus];
    }

    public function courseUpdate(Request $request, int $id){

        $response = [];
        $httpReqStatus = 200;
        $tags = $request->input('tags') ?? [];
        $tagsId = [];
        $userId = $request->user()->id; 
        $haveNewTags = false;

        $courceDataValidation = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'status' => 'nullable|in:published,draft,archived',
            'is_premium' => 'nullable|boolean',
        ]);

        if ($courceDataValidation->fails()) {
            $response = $this->statuses[0];
            $httpReqStatus = 422;
            $response["message"] = $courceDataValidation->errors();
            return ['response' => $response, "http_status" => $httpReqStatus];
        } 

        $course = Course::find($id);

        if($course){

            $dataOfCourse = $request->only('title', 'description','status', 'is_premium');
            $course->update($dataOfCourse);
            
            if($tags)
                $haveNewTags = $this->storeTags($tags);  

            if($haveNewTags){
                $course->tags()->detach();
                $tagsId = $this->getNewCourseTags($tags,$course); 
            }

            if($tagsId)
                $course->tags()->attach($tagsId);

            $course->load('tags');
            $response = $this->statuses[1];
            $response["data"] = $course;
            $response["message"] = "The course '{$course->title}' was updated successfully.";
            event(new CourseChange());

        }else{

            $response = $this->statuses[0];
            $httpReqStatus = 404;
            $response["message"] = "We couldn't find a course with ID {$id}.";
        }


        return ['response' => $response, "http_status" => $httpReqStatus];
    }



    public function coursePartialUpdate(Request $request, int $id){
    
        $response = [];
        $httpReqStatus = 200;
        $tags = $request->input('tags') ?? [];
        $tagsId = [];
        $userId = $request->user()->id; 
        $haveNewTags = false;

        $courceDataValidation = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:published,draft,archived',
            'is_premium' => 'sometimes|boolean',
        ]);

        if ($courceDataValidation->fails()) {
            $response = $this->statuses[0];
            $httpReqStatus = 422;
            $response["message"] = $courceDataValidation->errors();
            return ['response' => $response, "http_status" => $httpReqStatus];
        } 

        $course = Course::find($id);

        if($course){

            $dataOfCourse = $request->only('title', 'description','status', 'is_premium');
            $course->update($dataOfCourse);
            
            if($tags)
                $haveNewTags = $this->storeTags($tags);  

            if($haveNewTags){
                $course->tags()->detach();
                $tagsId = $this->getNewCourseTags($tags,$course); 
            }

            if($tagsId)
                $course->tags()->attach($tagsId);


            $course->load('tags');
            $response = $this->statuses[1];
            $response["data"] = $course;
            $response["message"] = "Course partially updated";
            event(new CourseChange());

        }else{

            $response = $this->statuses[0];
            $httpReqStatus = 404;
            $response["message"] = "We couldn't find a course with ID {$id}.";
        }

        return ['response' => $response, "http_status" => $httpReqStatus];
    }


    public function getFilterCourses(Request $request){

        $data = $request->input();
        $response = $this->statuses[1];
        $courses = Course::filterCourses($data)->with('tags')->get();
        $response['data'] = $courses;
        return ['response' => $response, "http_status" => 200];    
    }


    // public function getFilterCourses(Request $request)
    // {
    //     $query = Course::query()->with('tags');
    //     $filterable = Schema::getColumnListing('courses');

    //     foreach ($request->query() as $key => $value) {
    //         if (in_array($key, $filterable)) {
    //             // Convert 'true'/'false' string to actual boolean
    //             if ($value === 'true' || $value === 'false') {
    //                 $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
    //             }
    //             $query->where($key, $value);
    //         }
    //     }

    //     // Special case: filter by tag(s)
    //     if ($request->has('tag')) {
    //         $tags = is_array($request->tag) ? $request->tag : explode(',', $request->tag);
    //         foreach ($tags as $tag) {
    //             $query->whereHas('tags', function ($q) use ($tag) {
    //                 $q->where('name', $tag);
    //             });
    //         }
    //     }
 
    //     $courses = $query->get();
    //     $response = $this->statuses[1]; 
    //     $response['data'] = $courses;
    //     return ['response' => $response, "http_status" => 200];  
 
    // }

}