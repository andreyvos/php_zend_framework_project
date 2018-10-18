<?php

    class T3Feed {


        public static function getID($webmasterID, $url, $leadProduct)
        {

            $channel = new T3Channel_JsForm();

            $ch = $channel->createFromRequest($webmasterID, $url, $leadProduct);
            
            $res = $this->database->fetchRow("select * from channels_js_forms where url=?", array($ch->url));

            if (!$res)
            {
                return $ch->insertIntoDatabase();
            }
            else
            {
                return $res['id'];
            }

            return 0;

        }

    }
