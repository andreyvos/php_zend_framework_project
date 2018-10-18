<?php

class T3CallCenter_AfterBecameProcessed
{
    public static function CallAction($obj, $from, $moreInfo = "")
    {
        if ($from == "return")
        {
          T3BuyerReturnPings::getInstance()->recieveFromCallCenter($obj);

            
        }
        else if ($from == "EventNumberTwo")
        {
            // ...
        }
    }

}