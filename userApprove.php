<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Model\Group\GroupUsers;
use App\Model\Organization\EmailTemplate;
use App\Model\Organization\EmailLayout;
use Session;

class userApprove extends Mailable
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
        $template_id = json_decode(get_organization_meta('user_notification_template',true));
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

        $email = Session::get('approveUser');
        $userEmail = GroupUsers::where('email',$email)->first()['email'];

        $sendFrom = get_organization_meta('from_email');
        
            if($sendFrom != null){
                $from = $sendFrom;
            }else{
                $from = 'oxosolutionsindia@gmail.com';
            }

        return $this->from($from)
                ->subject($emailTemplate['subject']) 
                ->view('organization.login.signup-email-template')
                ->with(['emailTemplate' => $emailTemplate,'emailLayout' => $emailLayout ,'userEmail' => $userEmail]);

    }
}
