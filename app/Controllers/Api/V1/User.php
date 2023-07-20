<?php

namespace App\Controllers\API\V1;

use App\Controllers\BaseController;
use App\Models\ApplicationModel;
use CodeIgniter\API\ResponseTrait;

class User extends BaseController
{
    use ResponseTrait;
    public function profile()
    {

        $userId = $this->request->attributes['user_id'];
        $role = $this->request->attributes['role'];

        // Use the $userId as needed in your controller method

        // For example, fetch the user's profile based on the user ID
        $profileModel = new ApplicationModel('users', 'user_id');

        $profile = $profileModel->select(['first_name', 'last_name', 'role'])->join('profiles', 'users.user_id=profiles.user_id', 'left')->where('users.user_id', $userId)->first() ?? [];

        if (!$profile) {
            // Profile not found for the user
            return $this->respond([
                'status' => true,
                'message' => 'Profile does not exits.',
            ], 400);
        }

        // Return the user's profile
        return $this->respond([
            'status' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => $profile
        ], 200);
    }

    public function profile_update()
    {
        if ($this->request->getMethod() == 'post') {
            $userId = $this->request->attributes['user_id'];
            $role = $this->request->attributes['role'];
            $rules = [
                'first_name' => 'required|max_length[100]',
                'last_name' => 'required|max_length[100]',
                // 'role' => 'required|in_list[editor,writer]'
            ];

            if (!$this->validate($rules)) {
                $validationErrors = $this->validator->getErrors();
                return $this->respond([
                    'status' => false,
                    'message' => 'Validation error occurs.',
                    'formErrors' => $validationErrors
                ], 400);
            } else {
                $postData = $this->request->getPost();

                $profileModel = new ApplicationModel('profiles', 'profile_id');
                $profileData = [

                    'first_name' => $this->request->getVar('first_name'),
                    'last_name' => $this->request->getVar('last_name')
                    // Add more fields as needed
                ];
                $check = $profileModel->select(['profile_id'])->where('user_id', $userId)->first() ?? [];
                if ($check) {
                    $profileData['profile_id'] = $check['profile_id'];
                    $x = $profileModel->save($profileData);
                    unset($profileData['profile_id']);
                } else {
                    $profileData['user_id'] = $userId;
                    $x = $profileModel->insert($profileData);
                    unset($profileData['user_id']);
                }

                // Perform the update

                if ($x) {
                    /*
                    $token = null;
                    if ($role != $postData['role']) {
                        $userModel = new ApplicationModel('users', 'user_id');
                        $y = $userModel->update($userId, ['role' => $postData['role']]);
                        if ($y) {
                            $role = $postData['role'];
                            $token = $this->generateJWTToken($userId, $postData['role']);
                        }
                    }

                    $profileData['token'] = $token;
                    */
                    $profileData['role'] = $role;
                    return $this->respond(['status' => true, 'message' => 'Update profile successfully', 'data' => $profileData]);
                } else {
                    return $this->respond(['status' => false, 'message' => 'Internal server error occurs.'], 500);
                }
            }
        }
        return $this->respond([
            'status' => false,
            'message' => 'The requested action is not allowed.'
        ], 405);
    }

    /************ Writer Start ***********/
    public function add_post()
    {
        if ($this->request->getMethod() == 'post') {

            $role = $this->request->attributes['role'];
            if ($role != 'writer') {
                return $this->respond(['status' => false, 'message' => 'Access frobidden'], 403);
            }
            $userId = $this->request->attributes['user_id'];
            $rules = [
                'title' => 'required|max_length[255]',
                'post' => 'required|string'
            ];
            if (!$this->validate($rules)) {
                $validationErrors = $this->validator->getErrors();
                return $this->respond([
                    'status' => false,
                    'message' => 'Validation error occurs.',
                    'formErrors' => $validationErrors
                ], 400);
            } else {

                $articleModel = new ApplicationModel('articles', 'article_id');
                $articleData = [
                    'unique_id' => uniqid('AI'),
                    'user_id' => $userId,
                    'title' => $this->request->getVar('title'),
                    'post' => $this->request->getVar('post'),
                    //'article_status'=>'Dective',
                ];
                $articleId = $articleModel->insert($articleData);
                if ($articleId) {
                    unset($articleData['user_id']);
                    return $this->respond(['status' => true, 'message' => 'Add post successfully.', 'data' => $articleData]);
                } else {
                    return $this->respond(['status' => false, 'message' => 'Internal server error occurs.'], 500);
                }
            }
        }
        return $this->respond([
            'status' => false,
            'message' => 'The requested action is not allowed.'
        ], 405);
    }

    public function posts()
    {
        $role = $this->request->attributes['role'];
        if ($role != 'writer') {
            return $this->respond(['status' => false, 'message' => 'Access frobidden'], 403);
        }
        $userId = $this->request->attributes['user_id'];
        $title = $this->request->getVar('title');
        $perPage = (int) $this->request->getVar('per_page');
        $perPage = $perPage ? $perPage : 10;
        $currentPage = $this->request->getVar('page') ?? 1;


        $articleModel = new ApplicationModel('articles', 'article_id');
        $articleModel->where(['user_id' => $userId, 'article_deleted_at' => null]);
        if ($title) {
            $articleModel->like('title', $title);
        }
        // Count the total number of filtered posts
        $totalFilteredPosts = $articleModel->countAllResults(false);

        $totalPages = ceil($totalFilteredPosts / $perPage);

        $posts = $articleModel->select(['title', 'post', 'unique_id', 'article_status', 'article_created_at'])->orderBy('article_id', 'desc')->paginate($perPage, 'page', $currentPage);

        return $this->respond([
            'status' => true,
            'message' => 'Post list retrieved successfully.',
            'data' => [
                'total_filtered_posts' => $totalFilteredPosts,
                'total_pages' => $totalPages,
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'posts' => $posts
            ]
        ]);
    }

    public function post_detail($unique = false)
    {
        if ($unique == false) {
            return $this->respond(['status' => false, 'message' => 'Unique id is not valid.', 'formErrors' => [
                'unique_id' => 'Unique id is not valid.'
            ]], 400);
        }
        $role = $this->request->attributes['role'];
        if ($role != 'writer') {
            return $this->respond(['status' => false, 'message' => 'Access frobidden'], 403);
        }
        $userId = $this->request->attributes['user_id'];
        $articleModel = new ApplicationModel('articles', 'article_id');
        $post = $articleModel->select(['title', 'post', 'unique_id', 'article_status'])->where(['user_id' => $userId, 'article_deleted_at' => null, 'unique_id' => $unique])->first() ?? [];

        if (!$post) {
            return $this->respond(['status' => false, 'message' => 'Unique id is not valid.', 'formErrors' => [
                'unique_id' => 'Unique id does not exit.'
            ]], 400);
        }

        return $this->respond([
            'status' => true,
            'message' => 'Fetch post detail successfully.',
            'data' => $post
        ]);
    }

    public function post_update($unique = false)
    {
        if ($this->request->getMethod() == 'post') {
            if ($unique == false) {
                return $this->respond(['status' => false, 'message' => 'Unique id is not valid.', 'formErrors' => [
                    'unique_id' => 'Unique id is not valid.'
                ]], 400);
            }
            $role = $this->request->attributes['role'];
            if ($role != 'writer') {
                return $this->respond(['status' => false, 'message' => 'Access frobidden'], 403);
            }
            $userId = $this->request->attributes['user_id'];
            $rules = [
                'title' => 'required|max_length[255]',
                'post' => 'required|string',
                'status' => 'required|in_list[Active,Dective]'
            ];
            if (!$this->validate($rules)) {
                $validationErrors = $this->validator->getErrors();
                return $this->respond([
                    'status' => false,
                    'message' => 'Validation error occurs.',
                    'formErrors' => $validationErrors
                ], 400);
            } else {

                $articleModel = new ApplicationModel('articles', 'article_id');
                $post = $articleModel->select(['article_id'])->where(['user_id' => $userId, 'article_deleted_at' => null, 'unique_id' => $unique])->first() ?? [];

                if (!$post) {
                    return $this->respond(['status' => false, 'message' => 'Unique id is not valid.', 'formErrors' => [
                        'unique_id' => 'Unique id does not exit.'
                    ]], 400);
                }
                $articleData = [
                    'article_id' => $post['article_id'],
                    'title' => $this->request->getVar('title'),
                    'post' => $this->request->getVar('post'),
                    'article_status' => $this->request->getVar('status'),
                ];
                $articleId = $articleModel->save($articleData);
                if ($articleId) {
                    return $this->respond(['status' => true, 'message' => 'Update post successfully.', 'data' => $this->request->getPost()]);
                } else {
                    return $this->respond(['status' => false, 'message' => 'Internal server error occurs.'], 500);
                }
            }
        }
        return $this->respond([
            'status' => false,
            'message' => 'The requested action is not allowed.'
        ], 405);
    }

    public function delete_post($unique = false)
    {
        if ($unique == false) {
            return $this->respond(['status' => false, 'message' => 'Unique id is not valid.', 'formErrors' => [
                'unique_id' => 'Unique id is not valid.'
            ]], 400);
        }
        $role = $this->request->attributes['role'];
        if ($role != 'writer') {
            return $this->respond(['status' => false, 'message' => 'Access frobidden'], 403);
        }
        $userId = $this->request->attributes['user_id'];
        $articleModel = new ApplicationModel('articles', 'article_id');
        $post = $articleModel->select(['article_id'])->where(['user_id' => $userId, 'article_deleted_at' => null, 'unique_id' => $unique])->first() ?? [];

        if (!$post) {
            return $this->respond(['status' => false, 'message' => 'Unique id is not valid.', 'formErrors' => [
                'unique_id' => 'Unique id does not exit.'
            ]], 400);
        }
        $x = $articleModel->save(['article_id' => $post['article_id'], 'article_deleted_at' => date('Y-m-d H:i:s')]);
        if ($x) {
            return $this->respond([
                'status' => true,
                'message' => 'Delete post successfully.',
            ]);
        } else {
            return $this->respond(['status' => false, 'message' => 'Internal server error occurs.'], 500);
        }
    }

    public function post_comments($unique = false)
    {
        if ($unique == false) {
            return $this->respond(['status' => false, 'message' => 'Unique id is not valid.', 'formErrors' => [
                'unique_id' => 'Unique id is not valid.'
            ]], 400);
        }
        $role = $this->request->attributes['role'];
        if ($role != 'writer') {
            return $this->respond(['status' => false, 'message' => 'Access frobidden'], 403);
        }
        $userId = $this->request->attributes['user_id'];
        $articleModel = new ApplicationModel('articles', 'article_id');
        $post = $articleModel->select(['article_id'])->where(['user_id' => $userId, 'article_deleted_at' => null, 'unique_id' => $unique])->first() ?? [];

        if (!$post) {
            return $this->respond(['status' => false, 'message' => 'Unique id is not valid.', 'formErrors' => [
                'unique_id' => 'Unique id does not exit.'
            ]], 400);
        }
        $perPage = (int) $this->request->getVar('per_page');
        $perPage = $perPage ? $perPage : 10;
        $currentPage = $this->request->getVar('page') ?? 1;
        $commentModel = new ApplicationModel('comments', 'comment_id');
        $commentModel->where('article_id', $post['article_id']);
        // Count the total number of filtered posts
        $totalFilteredPosts = $commentModel->countAllResults(false);
        $totalPages = ceil($totalFilteredPosts / $perPage);
        $comments = $commentModel->select(['comment', 'comment_status', 'comment_created_at', 'CONCAT(COALESCE("first_name","N/A"), " " ,COALESCE("last_name","")) name'])->join('profiles', 'comments.user_id=profiles.user_id', 'left')->orderBy('comment_id', 'desc')->paginate($perPage, 'page', $currentPage);

        return $this->respond([
            'status' => true,
            'message' => 'Post comments retrieved successfully.',
            'data' => [
                'total_filtered_posts' => $totalFilteredPosts,
                'total_pages' => $totalPages,
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'posts' => $comments
            ]
        ]);
    }
    /************ Writer Stop ***********/

    public function all_posts()
    {

        $title = $this->request->getVar('title');
        $perPage = (int) $this->request->getVar('per_page');
        $perPage = $perPage ? $perPage : 10;
        $currentPage = $this->request->getVar('page') ?? 1;


        $articleModel = new ApplicationModel('articles', 'article_id');
        $articleModel->where(['article_status' => 'Active', 'article_deleted_at' => null]);
        if ($title) {
            $articleModel->like('title', $title);
        }
        // Count the total number of filtered posts
        $totalFilteredPosts = $articleModel->countAllResults(false);

        $totalPages = ceil($totalFilteredPosts / $perPage);

        $posts = $articleModel->select(['title', 'post', 'unique_id',  'article_created_at'])->orderBy('article_id', 'desc')->paginate($perPage, 'page', $currentPage);

        return $this->respond([
            'status' => true,
            'message' => 'All Post list retrieved successfully.',
            'data' => [
                'total_filtered_posts' => $totalFilteredPosts,
                'total_pages' => $totalPages,
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'posts' => $posts
            ]
        ]);
    }

    public function single_post($unique = false)
    {
        if ($unique == false) {
            return $this->respond(['status' => false, 'message' => 'Unique id is not valid.', 'formErrors' => [
                'unique_id' => 'Unique id is not valid.'
            ]], 400);
        }

        $articleModel = new ApplicationModel('articles', 'article_id');
        $post = $articleModel->select(['title', 'post', 'unique_id', 'article_created_at'])->where(['article_status' => 'Active', 'article_deleted_at' => null, 'unique_id' => $unique])->first() ?? [];

        if (!$post) {
            return $this->respond(['status' => false, 'message' => 'Unique id is not valid.', 'formErrors' => [
                'unique_id' => 'Unique id does not exit.'
            ]], 400);
        }

        return $this->respond([
            'status' => true,
            'message' => 'Fetch post detail successfully.',
            'data' => $post
        ]);
    }

    public function add_post_comment($unique = false)
    {
        if ($this->request->getMethod() == 'post') {
            if ($unique == false) {
                return $this->respond(['status' => false, 'message' => 'Unique id is not valid.', 'formErrors' => [
                    'unique_id' => 'Unique id is not valid.'
                ]], 400);
            }

            $userId = $this->request->attributes['user_id'];
            $rules = [
                'comment' => 'required|string',
            ];
            if (!$this->validate($rules)) {
                $validationErrors = $this->validator->getErrors();
                return $this->respond([
                    'status' => false,
                    'message' => 'Validation error occurs.',
                    'formErrors' => $validationErrors
                ], 400);
            } else {

                $articleModel = new ApplicationModel('articles', 'article_id');
                $post = $articleModel->select(['article_id', 'user_id'])->where(['article_deleted_at' => null, 'unique_id' => $unique, 'article_status' => 'Active'])->first() ?? [];

                if (!$post) {
                    return $this->respond(['status' => false, 'message' => 'Unique id is not valid.', 'formErrors' => [
                        'unique_id' => 'Unique id does not exit.'
                    ]], 400);
                }
                if ($post['user_id'] == $userId) {
                    return $this->respond(['status' => false, 'message' => 'Self comments are not allowed.', 'formErrors' => [
                        'unique_id' => 'Self comments are not allowed.'
                    ]], 400);
                }
                $commentModel =  new ApplicationModel('comments', 'comment_id');
                $commentData = [
                    'user_id' => $userId,
                    'article_id' => $post['article_id'],
                    'comment' => $this->request->getVar('comment'),
                ];
                $commentId = $commentModel->insert($commentData);
                if ($commentId) {
                    return $this->respond(['status' => true, 'message' => 'Add post comment successfully.', 'data' => $this->request->getPost()]);
                } else {
                    return $this->respond(['status' => false, 'message' => 'Internal server error occurs.'], 500);
                }
            }
        }
        return $this->respond([
            'status' => false,
            'message' => 'The requested action is not allowed.'
        ], 405);
    }
}
