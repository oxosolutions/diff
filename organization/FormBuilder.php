<?php

namespace App\Model\Organization;

use Illuminate\Database\Eloquent\Model;
use Auth;
use Session;

class FormBuilder extends Model
{

    protected $table = '';

    protected $fillable = ['field_slug', 'form_id', 'section_id', 'field_title','field_type','field_description','order'];

    public function __construct(){
            if(!empty(Session::get('organization_id'))){
                $this->table = Session::get('organization_id').'_form_fields';
            }
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
    public function fields()
    {
    	return $this->belongsTo('App\Model\Organization\section','id','section_id');
    }
    public function fieldMeta()
    {
        return $this->hasMany('App\Model\Organization\FieldMeta','field_id','id');
    }

    public function formsMeta(){
        return $this->hasMany('App\Model\Organization\FormsMeta','form_id','form_id');
    }
    
    public function setTable($table){
        
        $this->table = $table;
    }

    public function section(){
        return $this->belongsTo('App\Model\Organization\section','section_id','id');
    }
}
