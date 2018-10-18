<?php

    class T3Evercookie {

        public static function AddNewEntry($a) {
            T3Db::api()->insert('evercookie_leads', array(

                'dt' => date("Y:m:d H:i:s"),
                'ip' => $_SERVER['REMOTE_ADDR'],
                'evercookie' => $_GET['ec'],
                'lead_id' => $a['lead_id'],
                'email' => $a['email'],
                'fname' => $a['fname'],
                'lname' => $a['lname'],
                'zip' => $a['zip'],
                'ua' => $a['ua'],
                'wmid' => $a['wmid'],
                'subacc' => $a['subacc'],
                'url' => $a['url']

            ));

        }

        public static function AddNewEntryToAutoFillLog($a) {

            $fraudCoef = 0;

            try
            {
                $fraudCoef = FraudAutoFillSum::CalcLambda($a, $a['product']);

                FraudAutoFillSum::AddToWM($a['wmid'], $fraudCoef);

                T3Db::api()->insert('auto_fill_log', array(

                    'dt' => date("Y:m:d H:i:s"),
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'lead_id' => $a['lead_id'],
                    'email' => $a['email'],
                    'ua' => $a['ua'],
                    'wmid' => $a['wmid'],
                    'subacc' => $a['subacc'],

                    'product' => $a['product'],
                        
                    'os' => DetectOperationSystem::Detect(),

                    "ev_focus" => $a["ev_focus"],
                    "ev_blur" => $a["ev_blur"],
                    "ev_change" => $a["ev_change"],
                    "ev_click" => $a["ev_click"],
                    "ev_dblclick" => $a["ev_dblclick"],
                    "ev_error" => $a["ev_error"],
                    "ev_keydown" => $a["ev_keydown"],
                    "ev_keypress" => $a["ev_keypress"],
                    "ev_keyup" => $a["ev_keyup"],
                    "ev_mousedown" => $a["ev_mousedown"],
                    "ev_mousemove" => $a["ev_mousemove"],
                    "ev_mouseout" => $a["ev_mouseout"],
                    "ev_mouseover" => $a["ev_mouseover"],
                    "ev_mouseup" => $a["ev_mouseup"],
                    "ev_resize" => $a["ev_resize"],
                    "ev_select" => $a["ev_select"],
                    "fraud_coef" => $fraudCoef,
                ));

            } catch (Exception $E)
            {

            }

        }

    }