<?php

namespace App\Http\Requests\Players;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'sometimes|min:5|max:30',
            'description' => 'sometimes|min:10|max:255',
            'latitude' => 'sometimes|required_with:latitude|nullable|numeric|min:-90|max:90',
            'lontitude' => 'sometimes|required_with:longitude|nullable|numeric|min:-180|max:180',
            'timestamp' => 'required',
            'signature' => 'required',
        ];
    }
}
