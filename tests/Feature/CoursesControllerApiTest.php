<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Tag;
use Laravel\Passport\Passport;

class CoursesControllerApiTest extends TestCase
{

    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Passport::actingAs($this->user); 
    }

    public function test_user_can_get_all_courses()
    {
        Course::factory()->count(12)->create(['user_id' => $this->user->id]); 
        $response = $this->getJson('/api/courses?per_page=5&page=2'); 
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => '',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'courses' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'status',
                            'is_premium',
                            'user_id',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'pagination' => [
                        'total',
                        'count',
                        'per_page',
                        'current_page',
                        'total_pages',
                    ]
                ]
            ])
            ->assertJsonPath('data.pagination.current_page', 2)
            ->assertJsonPath('data.pagination.per_page', 5)
            ->assertJsonPath('data.pagination.total', 12)
            ->assertJsonPath('data.pagination.total_pages', 3);

        
        $this->assertCount(5, $response->json('data.courses'));
    }


    public function test_user_can_get_course_by_id()
    {

        $courses = Course::factory()->count(3)->create(['user_id' => $this->user->id]);
        $firstCourse = $courses->first();
        $firstCourseId = $firstCourse->id; 

        $response = $this->getJson("/api/courses/{$firstCourseId}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => '',
                'data' => [
                    'id' => $firstCourse->id,
                    'title' => $firstCourse->title,
                    'description' => $firstCourse->description,
                    'status' => $firstCourse->status,
                    'is_premium' => $firstCourse->is_premium,
                    'user_id' => $firstCourse->user_id,
                ]
            ]);
    }

 
    public function test_get_course_by_id_not_found()
    {
        $nonExistentId = 999999;

        $response = $this->getJson("/api/courses/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => "We couldn't find a course with ID {$nonExistentId}."
            ]);
    }



    public function test_user_can_store_course_with_tags()
    {

        $payload = [
            'title' => 'New Laravel Course',
            'description' => 'Learn Laravel testing',
            'status' => 'published',
            'is_premium' => true,
            'tags' => ['php', 'laravel']
        ];

        $response = $this->postJson('/api/courses', $payload);

        // Assertions on response
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Course created successfully.',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'is_premium',
                    'user_id',
                    'created_at',
                    'updated_at',
                    'tags' => [
                        '*' => ['id', 'name', 'created_at', 'updated_at']
                    ]
                ]
            ]);

        // Get course ID from response
        $courseId = $response->json('data.id');

        // Check course exists in DB
        $this->assertDatabaseHas('courses', [
            'id' => $courseId,
            'title' => $payload['title'],
            'description' => $payload['description'],
            'user_id' => $this->user->id
        ]);

        // Check tags exist in DB
        foreach ($payload['tags'] as $tagName) {

            $this->assertDatabaseHas('tags', ['name' => $tagName]);
            $tagId = Tag::where('name', $tagName)->first()->id;

            $this->assertDatabaseHas('course_tag', [
                'course_id' => $courseId,
                'tag_id' => $tagId
            ]);
        }
    }

    public function test_validation_error_when_storing_invalid_course()
    {
        Passport::actingAs(User::factory()->create());

        $response = $this->postJson('/api/courses', [
            'title' => '', // Missing title
            'description' => '', // Missing description
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message' => ['title', 'description']
            ]);
    }


    public function test_user_can_delete_course()
    {

        // Create a course
        $course = Course::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Send DELETE request
        $response = $this->deleteJson("/api/courses/{$course->id}");

        // Assert response
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Course deleted successfully',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'is_premium',
                    'user_id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]
            ]);

        // Assert soft delete in database (if using SoftDeletes)
        $this->assertSoftDeleted('courses', [
            'id' => $course->id
        ]);
    }

    public function test_deleting_non_existing_course_returns_404()
    {

        $nonExistentId = 999;

        $response = $this->deleteJson("/api/courses/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => "Course with ID {$nonExistentId} not found.",
            ]);
    }



    public function test_user_can_update_a_course()
    {
 
        // Create a course
        $course = Course::factory()->create(['user_id' => $this->user->id]);

        // Prepare updated data
        $updatedData = [
            'title' => 'Updated Course Title',
            'description' => 'Updated course description.',
            'status' => 'published',
            'is_premium' => true,
            'tags' => ['UpdatedTag1', 'UpdatedTag2'],
        ];

        // Send PUT request
        $response = $this->putJson("/api/courses/{$course->id}", $updatedData);

        // Assert response
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => "The course 'Updated Course Title' was updated successfully.",
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'is_premium',
                    'user_id',
                    'created_at',
                    'updated_at',
                    'tags' => [
                        '*' => ['id', 'name', 'created_at', 'updated_at'],
                    ],
                ],
            ]);

        // Assert DB changes
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Updated Course Title',
            'description' => 'Updated course description.',
            'status' => 'published',
            'is_premium' => true,
        ]);

        // Check pivot table for tag relation
        foreach ($updatedData['tags'] as $tagName) {
            $this->assertDatabaseHas('tags', ['name' => $tagName]);
            $this->assertDatabaseHas('course_tag', [
                'course_id' => $course->id,
                'tag_id' => Tag::where('name', $tagName)->first()->id,
            ]);
        }
    }


    public function test_user_can_partially_update_course()
    {
        $course = Course::factory()->create(['user_id' => $this->user->id]);

        $payload = [
            'title' => 'Partially Updated Title',
            'tags' => ['partialTag1', 'partialTag2'],
        ];

        $response = $this->patchJson("/api/courses/{$course->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Course partially updated',
                'data' => [
                    'id' => $course->id,
                    'title' => 'Partially Updated Title',
                    'user_id' => $this->user->id,
                ]
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'is_premium',
                    'user_id',
                    'created_at',
                    'updated_at',
                    'tags',
                ]
            ]);

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => $payload['title'],
        ]);

        foreach ($payload['tags'] as $tag) {
            $this->assertDatabaseHas('tags', ['name' => $tag]);
        }
    }

}
