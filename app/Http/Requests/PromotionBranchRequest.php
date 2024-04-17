<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;


class PromotionBranchRequest extends FormRequest
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
            'promotion_branch_title' => 'required|string|min:3|max:255',
            'promotion_branch_description' => 'nullable|min:3|string|max:255',
            'promotion_branch_start_date' => 'nullable|min:3|string|max:50',
            'promotion_branch_end_date' => 'nullable|min:3|string|max:50',
            'promotion_branch_type' => 'nullable|string|min:3|string|max:50',
            'promotion_branch_status' => 'nullable|string|min:3|string|max:50',
            'branch_id' => 'required|exists:business_branches,id',
        ];
    }

    public function failedValidation(Validator $validator)

    {

        throw new HttpResponseException(response()->json([

            'success'   => false,

            'message'   => 'Validation errors',

            'promotions_branches'      => $validator->errors()

        ]));

    }
}
