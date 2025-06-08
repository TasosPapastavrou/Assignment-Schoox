<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Http\Request;

interface CourseRepositoryInterface
{
    public function getAllCourses();
    public function getCourseById(int $id);
    public function storeCourse(Request $request);
    public function courseDelete(int $id);
    public function courseUpdate(Request $request, int $id);
    public function coursePartialUpdate(Request $request, int $id);
}