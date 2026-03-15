<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = (string) $this->input('name', '');

        $name = preg_replace('/[ \t　]+/u', ' ', trim($name));

        $this->merge([
            'name' => $name,
        ]);
    }

    public function rules()
    {
        return [
            'name' => 'required|max:20',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|min:8|same:password',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'お名前を入力してください',
            'name.max' => 'お名前は20文字以内で入力してください',

            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスはメール形式で入力してください',
            'email.unique' => '既に使用されているメールアドレスです',

            'password.required' => 'パスワードを入力してください',
            'password.min' => 'パスワードは8文字以上で入力してください',

            'password_confirmation.required' => '確認用パスワードを入力してください',
            'password_confirmation.min' => '確認用パスワードは8文字以上で入力してください',
            'password_confirmation.same' => 'パスワードと一致しません',
        ];
    }
}
