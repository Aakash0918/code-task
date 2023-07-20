<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ApplicationModel;
use CodeIgniter\API\ResponseTrait;


class Auth extends BaseController
{
    use ResponseTrait;
    public function register()
    {
        if ($this->request->getMethod() == 'post') {
            $rules = [
                'email' => 'required|valid_email|max_length[191]|is_unique[users.email]',
                'mobile' => 'required|numeric|min_length[8]|max_length[14]',
                'password' => 'required|min_length[8]|max_length[32]',
                'confirm_password' => 'required|matches[password]',
                'role' => 'required|in_list[editor,writer]'
            ];

            if (!$this->validate($rules)) {
                $validationErrors = $this->validator->getErrors();
                return $this->respond(["status" => false, 'message' => 'Validation error occurs.', 'formErrors' => $validationErrors], 400);
            } else {
                $postData = $this->request->getPost();

                $userModel = new ApplicationModel('users', 'user_id');
                $userData = [
                    'email' => $postData['email'],
                    'mobile' => $postData['mobile'],
                    'password' => $postData['password'],
                    'role' => $postData['role'],
                    'status' => "Active"
                ];
                $x = $userModel->insert($userData);
                if ($x) {
                    $token = $this->generateJWTToken($x, $postData['role']);
                    unset($userData['password']);
                    unset($userData['status']);
                    return $this->respond(["status" => true, 'message' => 'Registration successfully.', 'data' => [
                        'token' => $token,
                        'detail' => $userData
                    ]], 200);
                } else {
                    return $this->respond(['status' => false, 'message' => 'Internal server error occurs.'], 500);
                }
            }
        }
        return $this->respond(['status' => false, 'message' => 'The requested action is not allwed.'], 405);
    }

    

    public function index()
    {
        if ($this->request->getMethod() == 'post') {
            $rules = [
                'email' => 'required|valid_email',
                'password' => 'required|min_length[8]|max_length[32]'
            ];

            if (!$this->validate($rules)) {
                $validationErrors = $this->validator->getErrors();
                return $this->respond([
                    "status" => false,
                    'message' => 'Validation error occurs.',
                    'formErrors' => $validationErrors
                ], 400);
            } else {
                $postData = $this->request->getPost();
                $userModel = new ApplicationModel('users', 'user_id');
                $user = $userModel->select(['user_id', 'mobile', 'role', 'password', 'status', 'deleted_at'])->where(['email' => $postData['email']])->first() ?? [];
                if (!$user) {
                    return $this->respond([
                        "status" => false,
                        'message' => 'Validation error occurs.',
                        'formErrors' => ['email' => 'Account does not exits.']
                    ], 400);
                }

                if ($user['deleted_at']) {
                    return $this->respond([
                        "status" => false,
                        'message' => 'Validation error occurs.',
                        'formErrors' => ['email' => 'Account does not exits.']
                    ], 400);
                }

                if ($user['status'] == 'Dective') {
                    return $this->respond([
                        "status" => false,
                        'message' => 'Validation error occurs.',
                        'formErrors' => ['email' => 'Account has been in dective.']
                    ], 400);
                }

                // ($plainPassword, $hashedPassword)
                if (!password_verify($postData['password'], $user['password'])) {
                    // Invalid credentials
                    return $this->respond([
                        "status" => false,
                        'message' => 'Validation error occurs.',
                        'formErrors' => ['password' => 'Invalid Credentials.']
                    ], 400);
                } else {
                    // Valid credentials, generate token and return success response
                    $token = $this->generateJWTToken($user['user_id'], $user['role']);
                    unset($user['password']);
                    unset($user['status']);
                    unset($user['user_id']);
                    unset($user['deleted_at']);
                    $user['email'] = $postData['email'];
                    return $this->respond([
                        "status" => true,
                        'message' => 'Login successful.',
                        'data' => [
                            'token' => $token,
                            'detail' => $user
                        ]
                    ], 200);
                }
            }
        }

        return $this->respond([
            'status' => false,
            'message' => 'The requested action is not allowed.'
        ], 405);
    }
}
