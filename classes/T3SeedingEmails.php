<?php

class T3SeedingEmails {

    public function emails($email,$password){

        $mail = new Zend_Mail_Storage_Pop3(array('host'     => 'pop.mail.yahoo.com',
            'user'     => $email,
            'password' => $password,
            'port'     => 995,
            'ssl'      => 'SSL'));

        $result = array();

        foreach ($mail as $message) {
            $subject = $message->subject;
            $info = $message->getHeaders();

            $part = $message;
            while ($part->isMultipart()) {
                $part = $message->getPart(1);
            }
            $content = $part->getContent();

            $result[] = array(
                'from'=>$info['from'],
                'subject'=>$subject,
                'date'=>date("Y-m-d H:i:s",strtotime($info['date'])),
                'body'=>$content
            );
        }

        return $result;
    }

    public function check_today_emails(){
        $emails = T3DB::api()->fetchAll("select * from seeding_emails where (unix_timestamp()-unix_timestamp(date_send))<86400");
        foreach ($emails as $item){
            $list = $this->emails($item['email'],$item['password']);
            foreach ($list as $elem){
                $isset = T3DB::api()->fetchOne("select `id` from `seeding_emailslist` where `email_id`='".$item['id']."' and `date`='".$elem['date']."' and `subject`='".$elem['subject']."'");
                if (!$isset){
                    $array = array(
                        'email_id'=>$item['id'],
                        'from'=>$elem['from'],
                        'date'=>$elem['date'],
                        'subject'=>$elem['subject'],
                        'body'=>$elem['body'],
                        'new'=>1
                    );
                    T3DB::api()->insert('seeding_emailslist',$array);

                    $task_id = T3DB::api()->fetchOne("select seeding_task.id from seeding_task,seeding_emails where seeding_task.lead_id=seeding_emails.lead_id and seeding_emails.id=".$item['id']);
                    T3DB::api()->query("UPDATE seeding_task SET email=email+1 WHERE id=".$task_id);
                }
            }
        }
    }

    public function check_months_emails(){
        $emails = T3DB::api()->fetchAll("select * from seeding_emails where (unix_timestamp()-unix_timestamp(date_send))<30*86400");
        foreach ($emails as $item){
            $list = $this->emails($item['email'],$item['password']);
            foreach ($list as $elem){
                $isset = T3DB::api()->fetchOne("select `id` from `seeding_emailslist` where `email_id`='".$item['id']."' and `date`='".$elem['date']."' and `subject`='".$elem['subject']."'");
                if (!$isset){
                    $array = array(
                        'email_id'=>$item['id'],
                        'from'=>$elem['from'],
                        'date'=>$elem['date'],
                        'subject'=>$elem['subject'],
                        'body'=>$elem['body'],
                        'new'=>1
                    );
                    T3DB::api()->insert('seeding_emailslist',$array);

                    $task_id = T3DB::api()->fetchOne("select seeding_task.id from seeding_task,seeding_emails where seeding_task.lead_id=seeding_emails.lead_id and seeding_emails.id=".$item['id']);
                    T3DB::api()->query("UPDATE seeding_task SET email=email+1 WHERE id=".$task_id);
                }
            }
        }
    }

}



