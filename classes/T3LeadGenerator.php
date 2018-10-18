<?

// Этот отвечает за генерацию лидов
class T3LeadGenerator {

    public static function GenerateRandomPaydayLead() {
        $request = array
                   (
                       'user_id' => '111',
                       'product' => 'payday',
                       'form' => array
                               (
                                   'requested_amount' => array("RandomElement", array('100', '300', '500')),
                                   'employer' => array("GenerateRow", array("str", 20, 22)),
                                   'job_title' => array("GenerateRow", array("str", 15, 15)),
                                   'active_military' => '0',
                                   'supervisor_name' => array("GenerateRow", array("str", 15, 15)),
                                   'supervisor_phone' => array("GenerateRow", array("num", 10, 10)),
                                   'supervisor_phone_ext' => '',
                                   'employer_address' => array("GenerateRow", array("str", 15, 15)),
                                   'employer_city' => array("GenerateRow", array("str", 15, 15)),
                                   'employer_state' => array("RandomElement", array('AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'DC', 'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY', 'GU', 'PR', 'VI')),
                                   'employer_zip' => array("GenerateRow", array("num", 5, 5)),
                                   'employed_months' => array("GenerateRow", array("num", 3, 3)),
                                   'ssn' => '235523414',
                                   'drivers_license_number' => array("GenerateRow", array("num", 9, 9)),
                                   'drivers_license_state' => array("RandomElement", array('AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'DC', 'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY', 'GU', 'PR', 'VI')),
                                   'income_type' => 'EMPLOYMENT',
                                   'pay_frequency' => 'BIWEEKLY',
                                   'monthly_income' => '2900',
                                   'pay_date1' => '2009-02-20',
                                   'pay_date2' => '2009-03-20',
                                   'direct_deposit' => '0',
                                   'bank_name' => 'assagagsd',
                                   'bank_phone' => array("GenerateRow", array("num", 10, 10)),
                                   'bank_aba' => array("GenerateRow", array("num", 10, 10)),
                                   'bank_account_number' => array("GenerateRow", array("num", 10, 10)),
                                   'bank_account_type' => 'checking',
                                   'bank_account_length_months' => '169',
                                   'first_name' => array("GenerateRow", array("str", 15, 15)),
                                   'last_name' => array("GenerateRow", array("str", 15, 15)),
                                   'mother_maiden_name' => array("GenerateRow", array("str", 15, 15)),
                                   'birth_date' => '1973-01-18',
                                   'address' => array("GenerateRow", array("str", 15, 15)),
                                   'city' => array("GenerateRow", array("str", 15, 15)),
                                   'state' => array("RandomElement", array('AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'DC', 'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY', 'GU', 'PR', 'VI')),
                                   'zip' => array("GenerateRow", array("num", 5, 5)),
                                   'own_home' => '1',
                                   'address_length_months' => '14',
                                   'email' => array("GenerateRow", array("str", 15, 15)),
                                   'home_phone' => array("GenerateRow", array("num", 10, 10)),
                                   'work_phone' => array("GenerateRow", array("num", 10, 10)),
                                   'work_phone_ext' => '',
                                   'best_time_to_call' => 'morning',
                               )
                   );


        //

        $A = $request['form'];

        foreach($A as $var => $value) {
            if (count($value)>1) {
                $v = T3LeadGenerator::$value[0]($value[1]);
                $A[$var] = $v;
                //
                //$Request.= "form[".$var."]=".$v."&";
            }
        }

        $request['form'] = $A;

        return $request;
    }

    public static function GenerateOneSymbol($StrArr) {
        $l = $StrArr[rand(0, count($StrArr)-1)];
        if (rand(0, 1) > 0) {
            $l = strtoupper($l);
        }
        return $l;
    }

    public static function RandomElement($array) {
        return $array[rand(0, count($array)-1)];
    }

    public static function GenerateRow($Arr) {
        $dataType = $Arr[0];
        $minLength = $Arr[1];
        $maxLength = $Arr[2];

        $StrArr = array(
                      "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z"
                  );

        $Result = "";
        $StringLength = rand($minLength, $maxLength);
        for ($i = 0; $i < $StringLength; $i++) {
            if ($dataType == "mix") {
                $l = "";
                if (rand(0, 1) > 0) {
                    $l = rand(0, 9);
                } else {
                    $l = T3LeadGenerator::GenerateOneSymbol($StrArr);
                }
                $Result.= $l;
            }
            elseif ($dataType == "num") {
                $Result.=rand(0, 9);
            }
            elseif ($dataType == "str") {
                $l = T3LeadGenerator::GenerateOneSymbol($StrArr);
                $Result.=$l;
            }
        }
        return $Result;
    }

    public static function CreateRequest() {
        $Request = "";
        $PostArray = array();

        foreach(T3LeadGenerator::$A as $var => $value) {
            if (count($value)>1) {
                $v = T3LeadGenerator::$value[0]($value[1]);
                $PostArray[$var] = $v;
                $Request.= "form[".$var."]=".$v."&";
            }
        }
        $Request = substr($Request, 0, strlen($Request)-1);
        return $Request;
    }


}






