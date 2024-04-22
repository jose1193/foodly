<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class BusinessRequest extends FormRequest
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
            'business_name' => 'required|string|min:3|max:255',
            'business_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048',
            'business_email' => 'nullable|email',
            'business_phone' => 'nullable|string|min:5|max:50',
            'business_address' => 'nullable|string|min:5|max:50',
            'business_zipcode' => 'nullable|string|min:2|max:50',
            'business_city' => 'nullable|string|min:3|max:50',
            'business_country' => 'nullable|string|min:3|max:50',
            'business_website' => 'nullable|string',
            'business_latitude' => 'nullable', 
            'business_longitude' => 'nullable',
            'category_id' => 'required|exists:categories,id',
            
            
        ];
    }


    public function failedValidation(Validator $validator)

    {

        throw new HttpResponseException(response()->json([

            'success'   => false,

            'message'   => 'Validation errors',

            'errors'      => $validator->errors()

        ]));

    }


    
    public function messages()
{
    return [
        
        'user_id.exists' => 'The specified user does not exist.',
        
    ];
}


}
