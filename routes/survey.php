<?php
	
	Route::get('/surveys',								['as'=>'list.survey','uses'=>'survey\SurveyController@listSurvey']);
	Route::get('/survey/{form_id}/sections',			['as'=>'survey.sections.list','uses'=>'survey\SurveyController@sectionsList']);
	Route::get('/survey/stats/{id}',					['as'=>'stats.survey','uses'=>'survey\SurveyStatsController@stats']);
	Route::get('/survey/customize/{id}',				['as'=>'custom.survey','uses'=>'survey\SurveyController@custom']);
	Route::post('/survey/customize',					['as'=>'save.custom.survey','uses'=>'survey\SurveyController@save_custom']);
	Route::get('/survey/structure/{id}',				['as'=>'structure.survey','uses'=>'survey\SurveyStatsController@survey_structure']);
	Route::match(['get','post'],'/survey/reports/{id}', ['as'=>'survey.reports','uses'=>'survey\SurveyStatsController@reports']);
	Route::get('/survey/create',							['as'=>'create.survey','uses'=>'survey\SurveyController@createSurvey']);
	Route::POST('/survey/savet',							['as'=>'save.survey','uses'=>'survey\SurveyController@storeSurvey']);
	Route::get('/survey/settings/{id}',					['as'=>'survey.settings','uses'=>'survey\SurveyController@surveySettings']);
	Route::post('/survey/settings/save/{id}',			['as'=>'save.survey.settings','uses'=>'survey\SurveyController@saveSurveySettings']);
	Route::get('/survey/perview/{form_id}',				['as'=>'survey.perview','uses'=>'survey\SurveyController@surveyPerview']);
	Route::get('/survey/result',						['as'=>'result.survey','uses'=>'survey\SurveyController@resultSurvey']);
	Route::get('/survey/share/{id}',					['as'=>'share.survey','uses'=>'survey\SurveyController@shareSurvey']);
	Route::post('/survey/shareto/{id}',					['as'=>'save.shareto','uses'=>'survey\SurveyController@saveShareTo']);
	Route::get('/survey/shareto/delete/{id}',			['as'=>'survey.remove.shareto','uses'=>'survey\SurveyController@deleteShareTo']);
	Route::match(['get','post'],'/survey/result/{id?}', ['as'=>'results.survey','uses'=>'survey\SurveyStatsController@survey_result']);
	Route::match(['get','post'],'survey/report/{id}', 	['as'=>'survey.stats.report','uses'=>'survey\SurveyStatsController@survey_static_result']);
	Route::match(['get','post'],'survey/custom-report/{id}', 	['as'=>'custom.survey.report','uses'=>'survey\StaticSurveyResultController@survey_static_result']);
	Route::get('/survey/convert/{id}', 					['as'=>'survey.convert','uses'=>'survey\SurveyController@convertToDataset']);

	//ajax
	Route::get('/change/survey/status' , ['as' => 'change.share.status' , 'uses' => 'survey\SurveyController@changeShareStatus']);


?>