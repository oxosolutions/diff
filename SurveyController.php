<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Organization\forms;
use App\Model\Organization\FormsMeta;
use App\Model\Organization\User;
use App\Model\Admin\GlobalOrganization as GO;
use Session;
use Illuminate\Support\Facades\Schema;
use DB;
use App\Model\Organization\FieldMeta;
use App\Model\Organization\FormBuilder;
Use Carbon\carbon;
use App\Model\Organization\OrganizationSetting;
use App\Model\Group\GroupUsers;

class SurveyController extends Controller
{
    public function surveys(Request $request){
        // $org_id = GO::where('active_code',$request['activation_key'])->first()->id; 
         $org = GO::where('active_code',$request['activation_key']);
        if(!$org->exists()){
            return ['status'=>'error', 'message'=>'active code not exist'];
        }
        $org_id = $org->first()->id;
        $group_id = $org->first()->group_id;
        Session::put('organization_id',$org_id);
        Session::put('group_id',$group_id);
      //  $users = User::with('belong_group')->get()->keyBy('belong_group');
        $users = GroupUsers::with('organization_user.user_role_rel.roles')->has('organization_user')->get()->toArray();
        // dump($group_orgnization_user->toArray());
        // dd($group_orgnization_user->toArray());

        foreach ($users as $key => $value) {
           // array_push($users, $org_id);
           $users[$key]['org_id'] = $org_id; 
           $users[$key]['user_roles'] =  array_column(array_column($users[$key]['organization_user']['user_role_rel'], 'roles'), 'slug');

           unset($users[$key]['organization_user']); 



        }
        // dump($users);

 
        $form  =  forms::with(['section'=>function($query){
                                        $query->orderBy('order','asc');
                                    },
                                'section.sectionMeta',
                                'section.fields' =>function($query){
                                        $query->orderBy('order','asc');
                                },'section.fields.fieldMeta'] )->where('type','survey')->get();
        $smeta  =  forms::with(['section.sectionMeta'] )->where('type','survey')->get();
        $data["status"]= "success";
        $data["media"]= "";
        $question = [];
        $surveys =[];
        $groups =[];
        $repeater = [];
        foreach ($form as $key => $value) 
        {   $form_id = $value['id']; 
            $temp_form = ["id"=>$value['id'], "survey_table"=>'', "name"=>$value['form_title'], "created_by"=>'', "description"=>$value['form_description'], "status"=>'',"created_by"=>$value['created_by'], "created_at"=>date('Y-m-d',strtotime($value->created_at)), "updated_at"=>date('Y-m-d',strtotime($value->updated_at)), "deleted_at"=>'']; 
            array_push($surveys,$temp_form);
            if(!empty($value['section']))
            {
               $section[] = $value['section'];
               foreach ($value['section'] as $sectionKey => $sectionValue) 
               {
                // dump($sectionValue);
                $section_type = $sectionValue['sectionMeta']->where('key','section_type')->first();
                $section_type_value ="";
                if(!empty($section_type['key'])){
                    $section_type_value = $section_type['value'];
                }
                $section_id = $sectionValue['id']; 
                $temp_section =  ["id"=>$sectionValue['id'], "survey_id"=>$sectionValue['form_id'], "title"=>$sectionValue['section_name'], "description"=>$sectionValue['section_description'], "group_order"=>$sectionValue['order'],'section_type'=>$section_type_value, "created_at"=>date('Y-m-d',strtotime($sectionValue['created_at'])), "updated_at"=>date('Y-m-d',strtotime($sectionValue['updated_at'])), "deleted_at"=>''];

                    array_push($groups,$temp_section);
                    if(!empty($sectionValue['fields']))
                    {
                        $index=0;
                        $repeater_check =0;
                        foreach ($sectionValue['fields'] as $fieldKey => $fieldValue) 
                        {

                            $field_id  =$fieldValue['id'];
/*below code is for  Add Question id which have not*/                           
                            $field_add_question = FieldMeta::where(['form_id'=>$form_id , 'section_id'=>$section_id, 'field_id'=>$field_id,  'key'=>'question_id']);
                            if(!$field_add_question->exists()){
                                $field_meta = new FieldMeta();
                                $field_meta->form_id = $form_id;
                                $field_meta->section_id = $section_id;
                                $field_meta->field_id = $field_id;
                                $field_meta->key = 'question_id';
                                $field_meta->value = "SID".$form_id."_GID".$section_id."_QID".$field_id;
                                $field_meta->save();
                                }
                            $index++;
                            if(!empty($fieldValue['fieldMeta']))
                            {

                                $collect = collect($fieldValue['fieldMeta']);
                                $form_meta =  $collect->mapWithKeys(function($item){
                                    return [$item['key'] => $item['value']];
                                });

                                $form_fields =   ['question_text'=>$fieldValue['field_title'], 'question_type'=>$fieldValue['field_type'], 'question_key'=>'', "question_id"=> $fieldValue['id'], "question_message"=> '', "required"=> '', "pattern"=> '', "otherPattern"=>'', "survey_id"=> $fieldValue['form_id'], "group_id"=> $fieldValue['section_id'],
                                            "question_order"=>$fieldValue["order"], "question_desc"=> $fieldValue["field_description"], "created_at"=>$fieldValue['created_at'], "updated_at"=>$fieldValue['updated_at'], "deleted_at"=>'', "answers"=>[[]], "fields"=>"" ]; 
                                if(!empty($form_meta['question_id'])){
                                    $form_fields['question_key'] = $form_meta['question_id'];
                                } 
                                if(!empty($form_meta['field_validations'])){
                                    $form_fields['field_validations'] = json_decode($form_meta['field_validations'], true);
                                }else{
                                    $form_fields['field_validations'] ="";

                                } 
                                if(!empty($form_meta['field_conditions'])){
                                    $form_fields['field_conditions'] = json_decode($form_meta['field_conditions'], true);
                                }else{
                                    $form_fields['field_conditions'] ="";

                                }

                                


                                    $options = json_decode(@$form_meta['field_options'],true);
                                        $form_fields['next_question_key'] ="";
                                if($fieldValue['field_type']=='select' ||  $fieldValue['field_type']=='multi_select' || $fieldValue['field_type']== 'radio' || $fieldValue['field_type'] == 'checkbox')
                                {   $option = null;
                                    if(!empty($options)){
                                        for($i=0; $i < count($options); $i++){
                                           $option[$i]['option_type']= $fieldValue['field_type'];
                                           $option[$i]['option_value']= @$options[$i]['key'];
                                           $option[$i]['option_text']= @$options[$i]['value'];
                                           $option[$i]['option_next'] = "";
                                           if(!empty($options[$i]['go_to_question'])){
                                                $option[$i]['option_next']= @$options[$i]['go_to_question'];
                                           }
                                           $option[$i]['option_prompt']= '';
                                       }
                                    }
                                    $form_fields['answers'] = [$option];
                                }
                                 
                                    if($section_type_value =='repeater' && $repeater_check ==0 )
                                        {   $repeater_check = 1;
                                            
                                            $repeater_section =  ['question_text'=>'Fill the repeater', 'question_type'=>'repeater', 'question_key'=>$sectionValue['section_slug'], "question_id"=> $sectionValue['id'], "question_message"=> '', "required"=> '', "pattern"=> '', "otherPattern"=>'', "survey_id"=> $sectionValue['form_id'], "group_id"=> $sectionValue['id'],
                                                        "question_order"=>' ', "question_desc"=> 'repeted', "created_at"=>date('Y-m-d',strtotime($sectionValue['created_at'])), "updated_at"=>date('Y-m-d',strtotime($sectionValue['updated_at'])), "deleted_at"=>'', "answers"=>[[]],'field_conditions'=>[],'field_validations'=>[],'fields'=>[] ,'next_question_key'=>'' ];                                
                                                array_push($repeater_section['fields'] ,  $form_fields);
                                        }elseif($section_type_value =='repeater'){
                                                array_push($repeater_section['fields'] ,  $form_fields);  
                                        }else{
                                            array_push($question,$form_fields);
                                        }
                            }
                        }
                            if($section_type_value =='repeater'){
                                array_push($question,$repeater_section );
                            }
                    }
               }
            }
        }
            $data['questions']      = $question;
            $data['surveys']     = $surveys;           
            $data['groups']      = $groups;
            $data["users"]       = $users;//GroupUsers::all();
            $data['settings'] = OrganizationSetting::where('type','app')->pluck('value','key');
            $mediaArray = [];
            if(isset($data['settings']['android_application_logo'])){
                $mediaArray['android_application_logo'] = $data['settings']['android_application_logo'];
            }
            $data['media'] = $mediaArray;
            return $data;   
}
            public function surveyPerview($form_id)
            {

                dd($form_id);
            }

    public function save_app_survey_filled_data(Request $request){
        // dd($request->all());
        //dump($request['activation_code']);
         $organization = GO::where('active_code',$request['activation_code']);
         if($organization->exists()){
            $org_id = $organization->first()->id;
         }else{
            return $error['org_id_not_exist'] =  "Error: Organization Not Exist";
         }
        Session::put('organization_id',$org_id);
        $form_query = forms::where('id',$request['survey_id']);
        if($form_query->exists()) {
           $form = $form_query->first();
         }else{
            return $error['survey_id_not_exist'] =  "Error: Survey  Not Exist";
         }
        $form_id = $request['survey_id'];
        unset($request['_token'],$request['form_id'],$request['form_slug'],$request['form_title'] );
        $survey_data = json_decode($request['survey_data'],true);  
        $records = 0;
        foreach ($survey_data as $key => $value) {
            unset($colums, $values, $keys);
            $return = $this->create_alter_insert_survey_table($org_id, $form_id , $value);
            if($return){
                    $records++;
                }
        }
        return ['sucess'=>"$records Import successfully!"];
    }

    public function create_alter_insert_survey_table($org_id, $form_id,$data){
        $form_id    =   intval($form_id);
        $question   =   FormBuilder::with(['fieldMeta'=>function($query){
                            $query->where('key','question_id');
                        }])->where('form_id',$form_id)->get()->toArray();
        $questionId_slug = collect($question)->mapWithKeys(function($items){
            return [$items['field_meta'][0]['value']=>$items['field_slug']];
        })->toArray();
        $table_name = 'ocrm_'.$org_id.'_survey_results_'.$form_id;
        $form_meta = FormsMeta::where(['form_id'=>$form_id,'key'=>'survey_data_table', 'value'=>$table_name]);
        if(!$form_meta->exists() || $form_meta->count() >1){
            if($form_meta->count() >1 ){
                $form_meta->delete();
            }
            $formMeta = new FormsMeta();
            $formMeta->form_id = $form_id;
            $formMeta->key = 'survey_data_table';
            $formMeta->value = $table_name;
            $formMeta->save();
        }
            $prefix_field = ['ip_address', 'survey_started_on', 'survey_completed_on', 'survey_status','survey_submitted_by','survey_submitted_from','mac_address','imei','device_detail','created_by', 'created_at', 'deleted_at'];
        foreach ($data as $dataKey => $dataValue){
                    if($dataKey !="id" && !in_array($dataKey, $prefix_field)){
                        if(!empty($questionId_slug[$dataKey])) {
                            $dataKey = $questionId_slug[$dataKey];
                            $dataKey = str_replace('-', '_', $dataKey);
                        }

                        $dataKey = substr($dataKey, 0,62);

                        $colums[] =   "`$dataKey` text COLLATE utf8_unicode_ci DEFAULT NULL";
                         if(is_array($dataValue)) {
                            $dataValue = json_encode($dataValue);
                         }
                         if($dataKey=='accident_date'){
                            $dataValue =  carbon::parse($dataValue)->format('Y-m-d');
                         }
                         if($dataKey=='created_at'){
                            $date = explode('-',$dataValue);
                            $formatedDate = $date[0]."-".$date[1]."-".$date[2];
                            $dataValue =  carbon::parse($formatedDate)->format('Y-m-d');
                         }
                         $newDataKey = str_replace('-', '_', $dataKey);
                         $keys[] = $newDataKey;
                }
                if($dataKey=='created_at'){
                    $date = explode('-',$dataValue);
                    $formatedDate = $date[0]."-".$date[1]."-".$date[2];
                    $dataValue =  carbon::parse($formatedDate)->format('Y-m-d');
                 }
                 if(is_array($dataValue)) {
                            $dataValue = json_encode($dataValue);
                         }
                if($dataKey=='accident_date'){
                            $dataValue =  carbon::parse($dataValue)->format('Y-m-d');
                         }

                if($dataKey !="id"){
                    $newKey = str_replace('-', '_', $dataKey);
                    $values[$newKey] = $dataValue;
                }
             }
            
            $colums = array_unique($colums);
            $newTableName = str_replace('ocrm_', '', $table_name);
         if(Schema::hasTable($newTableName)){
            $keys = array_unique($keys);
            $table_column = Schema::getColumnListing($newTableName);
            $columnsdata  = collect($keys);
            $new_columns   = $columnsdata->diff($table_column)->toArray();
            if(!empty($new_columns)){
                foreach ($new_columns as $key => $value) {
                    DB::select("ALTER TABLE `{$table_name}` ADD `{$value}` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'alter field'");
                }
            }
      }else{ 
            $colums[] =    "`ip_address` varchar(255) COLLATE utf8_unicode_ci NULL DEFAULT  NULL";
            $colums[] =    "`survey_started_on` varchar(255) COLLATE utf8_unicode_ci NULL DEFAULT  NULL";
            $colums[] =    "`survey_completed_on` varchar(255) COLLATE utf8_unicode_ci NULL DEFAULT  NULL";
            $colums[] =    "`survey_status` text NULL COLLATE utf8_unicode_ci DEFAULT NULL";
            $colums[] =    "`survey_submitted_by` varchar(255) COLLATE utf8_unicode_ci NULL DEFAULT  NULL";
            $colums[] =    "`survey_submitted_from` varchar(255) COLLATE utf8_unicode_ci NULL DEFAULT  NULL";
            $colums[] =    "`mac_address` varchar(255) COLLATE utf8_unicode_ci  NULL DEFAULT  NULL";
            $colums[] =    "`imei` varchar(255) COLLATE utf8_unicode_ci NULL DEFAULT  NULL";
            $colums[] =    "`unique_id` varchar(255) COLLATE utf8_unicode_ci NULL DEFAULT  NULL";
            $colums[] =    "`device_detail` text COLLATE utf8_unicode_ci NULL DEFAULT  NULL";
            $colums[] =    "`created_by` int(11) COLLATE utf8_unicode_ci NULL";
            $colums[] =    "`created_at` text COLLATE utf8_unicode_ci  NULL DEFAULT NUll";
            $colums[] =    "`deleted_at` timestamp COLLATE utf8_unicode_ci NULL DEFAULT NULL";
            DB::select("CREATE TABLE `{$table_name}` ( " . implode(', ', $colums) . " ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
            DB::select("ALTER TABLE `{$table_name}` ADD `id` INT(100) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'Row ID' FIRST");
        }
       
            if(!empty($values['unique_id'])){
                if(!DB::table($newTableName)->where('unique_id',$values['unique_id'])->exists()){
                   DB::table($newTableName)->insert($values);
                   return true;
                }else{
                    return false; 
                }
            }else{
                $values = array_merge($values, ['created_at'=> date('Y-m-d')]);
                if(Session::has('inserted_id')){
                    $insert_id =  Session::get('inserted_id');
                    DB::table($newTableName)->where('id',$insert_id)->update($values);
                    return true;
                }
                 $id = DB::table($newTableName)->insertGetId($values);
                 Session::put('inserted_id', $id);
                 return true;
            }
    }
}
