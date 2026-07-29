<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{

    public function show(Request $request)
    {
        return response()->json([
            'id'    => $request->user()->id,
            'name'  => $request->user()->name,
            'email' => $request->user()->email,
            'phone' => $request->user()->phone ?? '',
        ]);
    }


    public function update(Request $request)
    {
        $user = $request->user();

        // ۱. اعتبارسنجی داده‌های ورودی
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:11'],

            // اعتبارسنجی رمز عبور (در صورتی که فیلد رمز عبور جدید پر شده باشد)
            'current_password'          => ['nullable', 'required_with:new_password'],
            'new_password'              => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            // پیام‌های خطای فارسی
            'name.required'              => 'وارد کردن نام و نام خانوادگی الزامی است.',
            'email.required'             => 'وارد کردن ایمیل الزامی است.',
            'email.email'                => 'فرمت ایمیل معتبر نیست.',
            'email.unique'               => 'این ایمیل قبلاً توسط کاربر دیگری ثبت شده است.',
            'current_password.required_with' => 'جهت تغییر رمز عبور، وارد کردن رمز عبور فعلی الزامی است.',
            'new_password.min'           => 'رمز عبور جدید باید حداقل ۸ کاراکتر باشد.',
            'new_password.confirmed'     => 'تکرار رمز عبور جدید مطابقت ندارد.',
        ]);

        // ۲. بررسی صحت رمز عبور فعلی (در صورت درخواست تغییر پسورد)
        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'رمز عبور فعلی وارد شده اشتباه است.'
                ], 422);
            }

            // ثبت رمز عبور جدید
            $user->password = Hash::make($request->new_password);
        }

        // ۳. به‌روزرسانی اطلاعات عمومی
        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        if (array_key_exists('phone', $validated)) {
            $user->phone = $validated['phone'];
        }

        $user->save();

        return response()->json([
            'message' => 'اطلاعات پروفایل با موفقیت بروزرسانی شد.',
            'user'    => [
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ]
        ], 200);
    }
}
