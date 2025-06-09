<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Tag;
use Illuminate\Support\Collection;
use App\Repositories\CourseRepositoryInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class CourseServices implements CourseRepositoryInterface
{
    private $statuses = [
            0 => ['status' => "error", "message"=> "", "data" => []],
            1 => ['status' => "success", "message"=> "", "data" => []],
    ];

    public function getAllCourses()
    {
        $courses = Course::all();
        
        $response = $this->statuses[1];
        $response["data"] = $courses;

        return ['response' => $response, "http_status" => 200];  
    }

    public function getCourseById(int $id){

        $response = [];
        $httpReqStatus = '';
        $course = Course::find($id);

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

        }else{

            $response = $this->statuses[0];
            $httpReqStatus = 404;
            $response["message"] = "We couldn't find a course with ID {$id}.";
        }

        return ['response' => $response, "http_status" => $httpReqStatus];
    }


}