<?php

namespace App\Model\Admin;

use Illuminate\Database\Eloquent\Model;
use Auth;
use Session;

class FormBuilder extends Model
{

    protected $table = 'global_form_fields';

    protected $fillable = ['field_slug', 'form_id', 'section_id', 'field_title','field_type','field_description','order','status'];
    
    public function fields()
    {
    	return $this->belongsTo('App\Model\Admin\section','id','section_id');
    }
    public function fieldMeta()
    {
        return $this->hasMany('App\Model\Admin\FieldMeta','field_id','id');
    }
    
    public function setTable($table){
        
        $this->table = $table;
    }

    public function section(){
        return $this->belongsTo('App\Model\Admin\section','section_id','id');
    }

    public function formsMeta(){
        return $this->hasMany('App\Model\Admin\FormsMeta','form_id','form_id');
    }
    public function listColumn()
    {  
        if(request()->route()->parameters()['id'] != null){
            $requestParameter = request()->route()->parameters()['id'];
        }else{
            $requestParameter = request()->route()->parameters()['form_id'];
        }

        $list = $this->where(['form_id' => $requestParameter , 'section_id' => $_GET['sections']])->orderBy('order','ASC')->pluck('field_title','id');
        return $list;
    }

    public function formFieldTypes()
    {
        $types = ['text' => 'Text', 
                    'textarea' => 'Textarea', 
                    'number' => 'Number', 
                    'email' => 'Email', 
                    'password' => 'Password', 
                    'datepicker' => 'Datepicker', 
                    'timepicker' => 'Timepicker', 
                    'code' => 'Code',
                    'auto-generator' => 'Auto-generator',
                    'image' => 'Image',
                    'file' => 'File',
                    'select' => 'Select',
                    'multi_select' => 'Multi-select',
                    'checkbox' => 'Checkbox',
                    'radio' => 'Radio',
                    'button' => 'Button',
                    'message' => 'Message',
                    'switch' => 'Switch',
                    'color' => 'Color',
                    'icon' => 'Icon'
                ];
        return $types;
    }
    public function surveyFieldTypes()
    {
        $types = [
                    'text' => 'Text', 
                    'checkbox' => 'Checkbox',
                    'radio' => 'Radio',
                    'select' => 'Select',
                    'textarea' => 'Textarea', 
                    'datepicker' => 'Datepicker', 
                    'location_picker' => 'Location Picker', 
                    'message' => 'Message',
                    'timepicker' => 'Timepicker', 
                    'number' => 'Number', 
                ];
        return $types;
    }
}
