<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Model\Organization\User;
use Session;
use App\Model\Organization\EmailTemplate;
use App\Model\Organization\EmailLayout;
use App\Model\Group\GroupUsers;

class forgetPassword extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
            $check_notification_status = get_organization_meta('key' , 'enable_forgot_password');
            $template_id = get_organization_meta('forgot_password_template');
            $emailTemplate = '';
            $emailLayout = '';

            if($template_id != null || !empty($template_id)){
                $get_template = EmailTemplate::with(['templateMeta'])->where('id',$template_id)->first();
                $emailTemplate = $get_template->toArray();
            }
            if($get_template->templateMeta != null || !empty($get_template->templateMeta)){
                foreach ($get_template->templateMeta as $key => $value) {
                    if($value->key == 'layout'){
                        if($value->value != ''){
                            $emailLayout = EmailLayout::where('id',$value->value)->get()->toArray()[0];
                        }
                    }
                }
            }
        // $email = Session::get('new_user_register_email');
        // $name = Session::put('new_user_register_name');

        // $userEmail = GroupUsers::where('email',$email)->first()['email'];
        // $userName = GroupUsers::where('email',$email)->first()['name'];

        // return $this->from('oxosolutionsindia@gmail.com')
        //         ->subject($emailTemplate['subject']) 
        //         ->view('organization.login.signup-email-template')
        //         ->with(['emailTemplate' => $emailTemplate,'emailLayout' => $emailLayout ,'userEmail' => $userEmail , 'userName' => $userName]);


        $userName = GroupUsers::where('id',Session::get('user_id'))->first()['name'];
        
        $sendFrom = get_organization_meta('from_email');
        if($sendFrom != null){
            $from = $sendFrom;
        }else{
            $from = 'oxosolutionsindia@gmail.com';
        }
        
        return $this->from($from)
                    ->subject('Reset Password')
                    ->view('organization.login.signup-email-template')
                    ->with(['userName' => $userName , 'emailTemplate' => $emailTemplate , 'emailLayout' => $emailLayout]);
    }
}
