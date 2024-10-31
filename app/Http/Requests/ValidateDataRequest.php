<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'toCurrency' => 'required|string|in:EUR,GBP,CAD,JPY,USD',
            'days' => 'required|string|in:DAYS_7,DAYS_14,DAYS_30,DAYS_60',
            'pr1OrPr2' => 'required|string|in:pr1,pr2'
        ];
    }

    public function messages()
    {
        return [
            'toCurrency.required' => 'The currency field is required.',
            'toCurrency.in' => 'The selected currency is invalid.',
            'days.required' => 'The days field is required.',
            'days.in' => 'The selected days is invalid.',
            'pr1OrPr2.required' => 'Choose Your Accommodation field is required.',
            'pr1OrPr2.in' => 'The selected your Accommodation is invalid.',
        ];
    }

}
