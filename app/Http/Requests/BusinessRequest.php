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
            'business_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10000',
            'business_email' => 'nullable|email',
            'business_address' => 'nullable|string',
            'business_zipcode' => 'nullable|string',
            'business_city' => 'nullable|string',
            'business_country' => 'nullable|string',
            'business_website' => 'nullable|string',
            'business_opening_hours' => 'nullable|string',
            'business_opening_date' => 'nullable|string',
            'business_latitude' => 'nullable|numeric', 
            'business_longitude' => 'nullable|numeric',
            'category_id' => 'required|exists:categories,id',
            
            
        ];
    }


    public function failedValidation(Validator $validator)

    {

        throw new HttpResponseException(response()->json([

            'success'   => false,

            'message'   => 'Validation errors',

            'business'      => $validator->errors()

        ]));

    }


    
    public function messages()
{
    return [
        
        'user_id.exists' => 'The specified user does not exist.',
        
    ];
}


}
