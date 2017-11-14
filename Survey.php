<?php

namespace App\Model\Organization;

use Illuminate\Database\Eloquent\Model;
use Session;

class Survey extends Model
{
	protected $fillable = ['survey_table', 'name', 'created_by', 'description', 'status'];
    public function __construct(){
    	if(!empty(Session::get('organization_id'))){
    		$this->table = Session::get('organization_id').'_surveys';
    	}
    }
}
