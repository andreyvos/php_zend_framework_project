<?php
/**
 * Created by PhpStorm.
 * User: kyle
 * Date: 1/22/15
 * Time: 10:22 AM
 */

class T3CreditReferenceAgency {

    static public function saveDetails(T3Lead $lead, T3BuyerChannel $posting, $details) {
        $data = array(
            "record_datetime" => date("Y-m-d H:i:s"),
            "lead_id" => $lead->id,
            "buyer_id" => $posting->buyer_id,
            "posting_id" => $posting->id,
            "agency_details" => $details
        );
        return T3Db::api()->insert("credit_reference_agency_details", $data);

    }

}