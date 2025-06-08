<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\CourseRepositoryInterface;

class CoursesController extends Controller
{
    protected CourseRepositoryInterface $courseRepository;

    public function __construct(CourseRepositoryInterface $courseRepository)
    {
        $this->courseRepository = $courseRepository;
    }

    public function getCourses(Request $request){
        $result = $this->courseRepository->getAllCourses();
        return response()->json($result['response'], $result['http_status']);
    }

    public function getCourse(Request $request, $id){
        $result = $this->courseRepository->getCourseById($id);
        return response()->json($result['response'], $result['http_status']);
    }

    public function storeCourse(Request $request){
        $result = $this->courseRepository->storeCourse($request);
        return response()->json($result['response'], $result['http_status']);
    }

    public function courseDelete(Request $request, $id){
        $result = $this->courseRepository->courseDelete($id);
        return response()->json($result['response'], $result['http_status']);
    }

    public function courseUpdate(Request $request, $id){
        $result = $this->courseRepository->courseUpdate($request,$id);
        return response()->json($result['response'], $result['http_status']);
    }

    public function coursePartialUpdate(Request $request, $id){
        $result = $this->courseRepository->coursePartialUpdate($request, $id);
        return response()->json($result['response'], $result['http_status']);
    }
}
