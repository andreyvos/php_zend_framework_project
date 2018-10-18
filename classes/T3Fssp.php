<?php

// Proveryaem est' li u leada ispolnitelnoe proizvodstvo v slujbe sudebnih pristavov (rupayday)
class T3Fssp {

    public function getFSSPRusRecords($first_name, $last_name, $patronymic, $birth_date, $region) {
        $customer_data = array();
        $customer_data['name'] = $last_name . " " . $first_name . " " . $patronymic;
        $customer_data['date'] = date("d.m.Y", strtotime($birth_date));
        $customer_data["region"] = $region;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => "http://vk.fssprus.ru/app/search/?" . http_build_query($customer_data),
            CURLOPT_TIMEOUT => 5,
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        if ($response) {
            $fssp_data = json_decode($response);
            if ($fssp_data) {
                return $fssp_data->total;
            }
        }

        return "error";
    }

}

?>
