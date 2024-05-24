<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
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
      public function rules()
{
    $serviceId = $this->route('service') ? $this->route('service')->id : null;
    $isStoreRoute = $this->is('api/services/store');


    return [
        'service_name' => [
            $isStoreRoute ? 'required' : 'sometimes',
            'string',
            'min:3',
            'max:255'
        ],
        'service_description' => 'nullable|string|max:255',
        'service_image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3048',
    ];
}


    public function failedValidation(Validator $validator)

    {

        throw new HttpResponseException(response()->json([

            'success'   => false,

            'message'   => 'Validation errors',

            'errors'      => $validator->errors()

        ], 422));

    }
public function messages()
{
    return [
        'service_name.unique' => 'The service name is already in use.',
        
    ];
}
}
