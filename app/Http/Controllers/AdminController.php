<?php

namespace App\Http\Controllers;

use App\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\User;
use Illuminate\Support\Facades\Hash;
use Validator;

class AdminController extends Controller
{
    // Login Function

    public function login(Request $request)
    {
        if($request->isMethod('POST'))
        {
            $data = $request->input();

            $adminCount = Admin::where(['username' => $data['username'], 'password' => md5($data['password'])])->count();

            // if(Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'admin' => '1']))
            if($adminCount == 1)
            {
                $adminStatus = Admin::where(['username' => $data['username'], 'password' => md5($data['password'])])->first();

                if($adminStatus->status == 1)
                {
                    Session::put('adminSession', $data['username']);

                    return redirect('/admin/dashboard');
                }
                else
                {
                    return redirect('/admin')->with('flash_message_error', 'Account Not Activated. Please ask other Admin to activate your account.');
                }
            }
            else
            {
                return redirect('/admin')->with('flash_message_error', 'Incorrect, please try again.');
            }
        }

        if(Session::has('adminSession'))
        {
            return redirect('/admin/dashboard');
        }

        return view('admin.admin_login');
    }

    // Dashboard Function

    public function dashboard()
    {
        // Validate Session to provide access

        /*
        if(Session::has('adminSession'))
        {
            // Perform Admin Task
        }
        else
        {
            return redirect('/admin')->with('flash_message_error', 'Please Login to access data.');
        }
        */

        return view('admin.dashboard');
    }

    // Settings Function

    public function settings()
    {
        $adminDetails = Admin::where(['username' => Session::get('adminSession')])->first();

        return view('admin.settings')->with(compact('adminDetails'));
    }

    // Check Password Function

    public function chkPassword(Request $request)
    {
        $data = $request->all();

        $adminCount = Admin::where(['username' => Session::get('adminSession'),'password'=>md5($data['current_pwd'])])->count();
        if ($adminCount == 1)
        {
            echo "true"; die;
        }
        else
        {
            echo "false"; die;
        }
    }

    // Update Password Function

    public function updatePassword(Request $request)
    {
        if($request->isMethod('POST'))
        {
            $data = $request->all();

            $adminCount = Admin::where(['username' => Session::get('adminSession'),'password'=>md5($data['current_pwd'])])->count();

            if ($adminCount == 1)
            {
                $password = md5($data['new_pwd']);
                Admin::where('username',Session::get('adminSession'))->update(['password'=>$password]);

                return redirect('/admin/settings')->with('flash_message_success', 'Password updated successfully.');
            }
            else
            {
                return redirect('/admin/settings')->with('flash_message_error', 'Current Password entered is incorrect.');
            }
        }
    }

    // Logout Function

    public function logout()
    {
        Session::flush();

        return redirect('/admin')->with('flash_message_success', 'Logged out Successfully');
    }

    // Add Admins/Sub-Admins Function

    public function addAdmin(Request $request)
    {
        if($request->isMethod('POST'))
        {
            $data = $request->all();

            $validator = Validator::make($request->all(), [
                'username' => 'required|min:4|alpha',
                'password' => 'required|min:8|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/'
            ]);

            if($validator->fails())
            {
                return redirect()->back()->withErrors($validator)->withInput($request->input());
            }

            $adminCount = Admin::where('username', $data['username'])->count();

            if($adminCount > 0)
            {
                return redirect()->back()->with('flash_message_error', 'Admin Username Already Taken, Please choose a different one')->withInput($request->input());
            }
            else
            {
                $admin = new Admin;
                $admin->username = $data['username'];
                $admin->password = md5($data['password']);
                if(empty($data['status']))
                {
                    $admin->status = '0';
                }
                else
                {
                    $admin->status = $data['status'];
                }

                $admin->save();

                return redirect()->back()->with('flash_message_success', 'Successfully added Admin');
            }
        }

        return view('admin.admins.add_admin');
    }

    // View Admins/Sub-Admins Function

    public function viewAdmins()
    {
        $admins = Admin::get();
        $adminCount = Admin::count();

        return view('admin.admins.view_admins')->with(compact('admins', 'adminCount'));
    }
}
