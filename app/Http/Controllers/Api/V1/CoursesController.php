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
        $response = $this->courseRepository->getAllCourses();
        return $response;
    }

    public function getCourse(Request $request, $id){
        $response = $this->courseRepository->getCourseById($id);
        return $response;
    }

    public function storeCourse(Request $request){
        $response = $this->courseRepository->storeCourse($request);
        return $response;
    }

    public function courseDelete(Request $request, $id){
        $response = $this->courseRepository->courseDelete($id);
        return $response;
    }

    public function courseUpdate(Request $request, $id){
        $response = $this->courseRepository->courseUpdate($request,$id);
        return $response;
    }

    public function coursePartialUpdate(Request $request, $id){
        $response = $this->courseRepository->coursePartialUpdate($request, $id);
        return $response;
    }
}
