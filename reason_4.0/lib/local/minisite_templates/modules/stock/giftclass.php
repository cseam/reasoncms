<?php
// PayFlowPro processing class
// This class encapsulates some of the work of handling a real-time transaction.
// The process is already fairly abstract -- what this class mostly does is provide
// some intelligent input and error checking and reporting so you don't have to code it
// into your app.  Note: expiration needs to be 4 digits, i.e. moyr.
//
// Usage:
//
// require("giftclass.php");
// $pf = new gift;
// $pf->set_info($Amount,$CardNumber,"$ExpMonth$ExpYear",$BudgetNumber,$NameOnCard,$Comment);
// $pf->set_recur($RecurringAmount,$StartDateMMDDYYY,$NoOfPayments,$PayPeriod,$CustomerEmail)
// $result = $pf->transact();
//    To operate in test mode, use $result = $pf->transact('test') instead.
// if (!$pf->approved)  {
//    $message = $pf->message;
//    ... do something with the error message
// } else {
//    ... do something with a successful transaction.  The returned values
//    you need to save as records of the transaction are these two:
//
//    echo $result['PNREF'];
//    echo $result['AUTHCODE'];
// }


include('pfproclass.php');
//require_once( $_SERVER['DOCUMENT_ROOT'] . '/alumni/gift/dbclass.php');
require_once( CARL_UTIL_INC . 'db/db.php');

class gift extends pfpc {

var $trans_details = array();
var $result = array();
var $giver_id;

	function transact($mode = '')  {
		if(isset($this->recur_amt)) {
			$this->result = parent::recur_transact($mode);
			if ($this->approved) $this->result['REFNUM'] = $this->result['RPREF'];
		} else {
			$this->result = parent::transact($mode);
			if ($this->approved) $this->result['REFNUM'] = $this->result['PNREF'];
		}
		if ($this->approved)  {
			$this->record_transaction($mode);
		}
		return $this->result;
	}

	function set_params($details) {
	 $this->trans_details = $details;
	 return true;
	}

	function set_confirmation_text($text) {
	//changed -sls
	/*
		$gift = new giftDB;
		$query = "UPDATE `gift_giver` SET
		confirmation_message = '".addslashes($text)."' WHERE id = '".$this->giver_id."'";
		$qresult = $gift->query($query);
	*/
		connectDB('reason_giving_form_connection');
	/*

		$qresult = db_query("UPDATE `gift_giver` SET
		confirmation_message = '".addslashes($text)."' WHERE id = '".$this->giver_id."'");
	*/

		$qresult = db_query("UPDATE `gift_giver` SET
		confirmation_message = '".addslashes($text)."' WHERE REFNUM = '".$this->result['REFNUM']."'");

		connectDB(REASON_DB);

	}

	function get_confirmation_text($id, $hash=0) {
	//changed -sls
	/*
		$gift = new giftDB;
		$query = "SELECT confirmation_message FROM `gift_giver` WHERE REFNUM='$id'";
		$qresult = $gift->query($query);

		if ($qresult) {
			$gift = mysql_fetch_array($qresult);
			return $gift['confirmation_message'];
		}
	*/
		connectDB('reason_giving_form_connection');

		$qresult = db_query("SELECT confirmation_message FROM `gift_giver` WHERE REFNUM='$id'");

		if ($qresult) {
			$gift = mysql_fetch_array($qresult);
			return $gift['confirmation_message'];
		}

		connectDB(REASON_DB);
	}

	function record_transaction($mode = '') {
		$install_types = array('Monthly' => 'MONT', 'Quarterly' => 'QTER', 'Yearly' => 'YEAR');
		// Handle transition between old giving forms and new. This could be removed once no one is
		// passing home_phone anymore.
		if (isset($this->trans_details['home_phone']))
		{
			$this->trans_details['phone'] = $this->trans_details['home_phone'];
			$this->trans_details['phone_type'] = 'Home';
		}

		//changed -sls
		//$gift = new giftDB;
		connectDB('reason_giving_form_connection');

		$qstring = "INSERT INTO `gift_giver` SET
		REFNUM='".addslashes($this->result['REFNUM'])."',
		submitter_ip='".addslashes($this->trans_details['submitter_ip'])."',
		first_name='".addslashes($this->trans_details['first_name'])."',
		last_name='".addslashes($this->trans_details['last_name'])."',
		spouse_first_name = '". (($this->trans_details['spouse_first_name'] == 'First') ? 'NULL' : $this->trans_details['spouse_first_name'])."',
		spouse_last_name = '". (($this->trans_details['spouse_last_name'] == 'Last') ? 'NULL' : $this->trans_details['spouse_last_name'])."',
		luther_affiliation='".addslashes(join("|", $this->trans_details['luther_affiliation']))."',
		class_year=".((!empty($this->trans_details['class_year'])) ? addslashes($this->trans_details['class_year']) : 'NULL').",
		address_1='".addslashes($this->trans_details['address_1'])."',
		address_2='".addslashes($this->trans_details['address_2'])."',
		city='".addslashes($this->trans_details['city'])."',
		state_province = '".addslashes($this->trans_details['state_province'])."',
		zip = '".addslashes($this->trans_details['zip'])."',
		country = '".addslashes($this->trans_details['country'])."',
		phone = '".addslashes($this->trans_details['phone'])."',
		phone_type = '".addslashes($this->trans_details['phone_type'])."',
		email = '".addslashes($this->trans_details['email'])."',
		credit_card_type = '".addslashes($this->trans_details['credit_card_type'])."',
		credit_card_number = '".addslashes($this->trans_details['credit_card_number'])."',
		credit_card_expiration_month = '".addslashes($this->trans_details['credit_card_expiration_month'])."',
		credit_card_expiration_year = '".addslashes($this->trans_details['credit_card_expiration_year'])."',
		credit_card_name = '".addslashes($this->trans_details['credit_card_name'])."'";
		// billing_address = '".addslashes($this->trans_details['billing_address'])."' ";
		if (array_key_exists('match_gift', $this->trans_details)) {
			$qstring .= ", match_gift = '". (($this->trans_details['match_gift'] == true) ? 'Yes': 'NULL')."'";
			$qstring .= ", employer_name = '".addslashes($this->trans_details['employer_name'])."'";
		}
		if (array_key_exists('estate_plans', $this->trans_details)) {
			if ( isset($this->trans_details['estate_plans'][0]) ) {
				$qstring .= ", have_estate_plans = 'Yes'";
			}
			if ( isset($this->trans_details['estate_plans'][1]) ) {
				$qstring .= ", send_estate_info = 'Yes'";
			}
		}
		if (array_key_exists('split_gift', $this->trans_details)) {
			$qstring .= ", split_gift = '". (($this->trans_details['split_gift'] == true) ? 'Yes': 'NULL')."'";

			if (array_key_exists('split_designations', $this->trans_details)) {
				$qstring .= ", split_designations = '".addslashes($this->trans_details['split_designations'])."'";
			}
		}
		if (array_key_exists('comments_special_instructions', $this->trans_details)) {
			$qstring .= ", comments = '". addslashes($this->trans_details['comments_special_instructions'])."'";
		}
		if (array_key_exists('gift_prompt', $this->trans_details)) {
			$qstring .= ", gift_prompt = '". addslashes($this->trans_details['gift_prompt'])."'";
			if (array_key_exists('gift_prompt_details', $this->trans_details)) {
				$qstring .= ", gift_prompt_details = '". addslashes($this->trans_details['gift_prompt_details'])."'";
			}
		}
		if (array_key_exists('dedication', $this->trans_details))
		{
			$qstring .= ", dedication = '".addslashes($this->trans_details['dedication'])."'";
			$qstring .= ", dedication_details = '".addslashes($this->trans_details['dedication_details'])."'";
		}
		if (array_key_exists('mail_receipt', $this->trans_details)) {
			$qstring .= ", mail_receipt = '".addslashes($this->trans_details['mail_receipt'])."' ";
		}
		if (array_key_exists('installment_notification', $this->trans_details)) {
			$qstring .= ", email_new_charges = '".addslashes($this->trans_details['installment_notification'])."' ";
		}
		// if (array_key_exists('billing_street_address', $this->trans_details)) {
		// 	$qstring .= ",
		// 	billing_street_address = '".addslashes($this->trans_details['billing_street_address'])."',
		// 	billing_city = '".addslashes($this->trans_details['billing_city'])."',
		// 	billing_state_province = '".addslashes($this->trans_details['billing_state_province'])."',
		// 	billing_zip = '".addslashes($this->trans_details['billing_zip'])."',
		// 	billing_country = '".addslashes($this->trans_details['billing_country'])."'";
		// }
		if (array_key_exists('refby', $this->trans_details)) {
			$qstring .= ",
			refby = '".addslashes($this->trans_details['refby'])."' ";
		}

		if ($mode == 'test') {
			$qstring .= ", status = 'TEST' ";
		}
			//changed -sls
		/*
			$qresult = $gift->query($qstring);

			if (empty($gift->insert_id)) return("Error recording transaction details.");
			$this->giver_id = $gift->insert_id;
		*/
			$qresult = db_query($qstring);



			if (isset($this->trans_details['gift_amount']) && ($this->trans_details['installment_type'] == 'Onetime')){
				$qstring = "INSERT INTO `gift_transaction` SET
				created=NOW(),
				amount = '".addslashes($this->trans_details[/* 'immediate_gift_amount' */'gift_amount'])."',
				PNREF = '". ((!empty($this->result['TRXPNREF'])) ? $this->result['TRXPNREF'] : $this->result['PNREF'])."',
				AUTHCODE = '".$this->result['AUTHCODE']."',
				giver_id = '"./* $this->giver_id */$this->result['REFNUM']."'";
				/*
					$qresult = $gift->query($qstring);
					if (empty($gift->insert_id)) return("Error recording transaction details.");
				*/
				$qresult = db_query($qstring);

			}


			if (isset($this->trans_details['gift_amount']) && ($this->trans_details['installment_type'] != 'Onetime'))  {
				$qstring = "INSERT INTO `gift_pledge` SET
				created=NOW(),
				amount = '".addslashes($this->trans_details[/* 'installment_amount' */'gift_amount'])."',
				payperiod = '".$install_types[$this->trans_details['installment_type']]."',
				start_date = '".addslashes($this->trans_details['installment_start_date'])."',
				end_date = '".addslashes($this->trans_details['installment_end_date'])."',
				RPREF = '".$this->result['RPREF']."',
				PROFILEID = '".$this->result['PROFILEID']."',
				giver_id = '"./* $this->giver_id */$this->result['REFNUM']."'";
			/*
				$qresult = $gift->query($qstring);
				if (empty($gift->insert_id)) return("Error recording transaction details.");
			*/
				$qresult = db_query($qstring);

			}
			connectDB(REASON_DB);
	}

}

?>