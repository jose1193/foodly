<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;



class BusinessCoverImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'business_image_path.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'business_id' => 'required|exists:businesses,id',
        ];
    }

public function failedValidation(Validator $validator)

    {

        throw new HttpResponseException(response()->json([

            'success'   => false,

            'message'   => 'Validation errors',

            'businessImages'      => $validator->errors()

        ]));

    }

    public function messages()
{
    return [
        
        'business_id.exists' => 'The specified business does not exist.',
        
    ];
}
}
