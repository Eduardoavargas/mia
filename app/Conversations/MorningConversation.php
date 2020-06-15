<?php

namespace App\Conversations;

class MorningConversation extends CoreConversation
{   
    public function run()
    {
        parent::run();
        $this->protocol = 'morning-protocol';        
        $this->checkPersonExists();
    }
}