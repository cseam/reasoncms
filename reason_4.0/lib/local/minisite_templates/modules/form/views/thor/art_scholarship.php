<?
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
reason_include_once('classes/plasmature/upload.php');
require('/var/phpmailer/PHPMailerAutoload.php');

$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'ArtScholarshipForm';

/**
 * IndividualVisitForm adds visit request info to Thor form
 * that gets personal info
 *
 * @author Steve Smith
 */

class ArtScholarshipForm extends DefaultThorForm
{
    var $statement_dest;
    var $portfolio_dest;
    var $portfolio;
    var $teacher_statement;

    function on_every_time()
    {
        $params = array(
            'acceptable_extensions' => array('pdf'),
            'acceptable_types' => array('application/pdf'),
            'allow_upload_on_edit' => true);

        $this->portfolio = $this->get_element_name_from_label('Portfolio (pdf)');
        $this->change_element_type($this->portfolio, 'ReasonUpload', $params);

        $this->teacher_statement = $this->get_element_name_from_label('Teacher\'s Statement (pdf)');
        $this->change_element_type($this->teacher_statement, 'ReasonUpload', $params);

        $personal_statement = $this->get_element_name_from_label('Personal Statement');
        $this->change_element_type($personal_statement, 'tiny_mce');
    }

    function process() // {{{
    {
            $portfolio = $this->get_element_name_from_label('Portfolio (pdf)');
            $teacher_statement = $this->get_element_name_from_label('Teacher\'s Statement (pdf)');
            $documents = array();

            array_push($documents, $this->get_element($portfolio), $this->get_element($teacher_statement));

            // see if document was uploaded successfully
            foreach ($documents as $document) {
                if(($document->state == 'received' OR $document->state == 'pending') AND file_exists( $document->tmp_full_path))
                {
                    $path_parts = pathinfo($document->tmp_full_path);
                    $suffix = (!empty($path_parts['extension'])) ? $path_parts['extension'] : '';

                    // if there is no extension/suffix, try to guess based on the MIME type of the file
                    if( empty( $suffix ) )
                    {
                        $type_to_suffix = array('application/pdf' => 'pdf');

                         $type = $document->get_mime_type();
                         if ($type) {
                             $m = array();
                             if (preg_match('#^([\w-.]+/[\w-.]+)#', $type, $m)) {
                                 // strip off any ;charset= crap
                                 $type = $m[1];
                                 if (!empty($type_to_suffix[$type]))
                                    $suffix = $type_to_suffix[$type];
                             }
                         }
                    }
                    if(empty($suffix))
                    {
                        $suffix = 'unk';
                        trigger_error('uploaded asset at '.$document->tmp_full_path.' had an indeterminate file extension ... assigned to .unk');
                    }

                    $dir = ASSET_PATH . 'art_scholarship_uploads/' . date('Y') . '/';
                    if (!is_dir($dir))
                    {
                        mkdir($dir, 0755 , true);
                    }

                    $first  = $this->get_value_from_label('First Name');
                    $last   = $this->get_value_from_label('Last Name');
                    if (end($documents) === $document)
                    {
                        $this->statement_dest = $dir . $first . $last . 'Statement.pdf';
                        touch($this->statement_dest);
                    } else {
                        $this->portfolio_dest = $dir . $first . $last . 'Portfolio.pdf';
                        touch($this->portfolio_dest);
                    }

                    //move the file - if windows and the destination exists, unlink it first.
                    if (server_is_windows() && file_exists($asset_dest))
                    {
                        unlink($asset_dest);
                    }
                    rename ($document->tmp_full_path, $this->statement_dest );
                    chown($this->statement_dest, REASON_SITE_DIRECTORY_OWNER);
                    chgrp($this->statement_dest, REASON_SITE_DIRECTORY_OWNER);
                    chmod($this->statement_dest, 0644);
                    rename ($document->tmp_full_path, $this->portfolio_dest );
                    chown($this->portfolio_dest, REASON_SITE_DIRECTORY_OWNER);
                    chgrp($this->portfolio_dest, REASON_SITE_DIRECTORY_OWNER);
                    chmod($this->portfolio_dest, 0644);
                }

                // make sure to ignore the 'pdf' fields
                $this->_process_ignore[] = $this->portfolio;
                $this->_process_ignore[] = $this->teacher_statement;
            }

            $this->email_form_data();
            // and, call the regular CM process method

            // give the form builder a something to look at in the database.
            // will also help others find the files in case there is a problem.
            $this->change_element_type($this->teacher_statement, 'text');
            $this->change_element_type($this->portfolio, 'text');
            if (is_null($this->get_value($this->portfolio)))
            {
                $this->set_value($this->portfolio, 'None provided');
            } else {
                $this->set_value($this->portfolio , $this->portfolio_dest);
            }
            if (is_null($this->get_value($this->teacher_statement)))
            {
                $this->set_value($this->teacher_statement, 'None provided');
            } else {
                $this->set_value($this->teacher_statement, $this->statement_dest);
            }
            parent::process();
    } // }}}

    function email_form_data()
    {
        $mail = new PHPMailer;
        $model =& $this->get_model();
        $sender = 'noreply@luther.edu';
        $recipient = $model->get_email_of_recipient();
        $recipients = explode(',',$recipient);

        if (in_array('@', $recipients)===FALSE){
               foreach($recipients as $recipient){
             $recipient .= '@luther.edu';
           }
        }

        $heading = "<h2><strong>".$model->get_form_name()."</strong></h2>";
        $email_data = $model->get_values_for_email();

        $values = "\n";
        if ($model->should_email_data())
        {
            foreach($email_data as $key => $val)
            {
                if (is_array($val['value'])) // if it's an array, then it's an upload
                {
                    $values .= sprintf("\n<strong>%s ::</strong>\t %s\n", $val['label'], $val['value']['name']);
                } else {
                    $values .= sprintf("\n<strong>%s ::</strong>\t %s\n", $val['label'], $val['value']);
                }
            }
        }
        $submission_time = date("Y-m-d H:i:s");
        $values .= sprintf("\n<strong>%s:</strong>\t    %s\n",'Form Submission Time', $submission_time);
        $vl = nl2br($values);
        $html_body = $heading . $vl;
        $txt_body = html_entity_decode(strip_tags($html_body));

        $mail->IsSendmail();
        $mail->AddReplyTo("noreply@luther.edu","No Reply");
        $mail->SetFrom("noreply@luther.edu","No Reply");
        $mail->AddAddress($recipient);
        $mail->AddAttachment($this->statement_dest);
        $mail->AddAttachment($this->portfolio_dest);
        $mail->Subject    = $model->get_form_name() . '-' . $this->get_value_from_label('First Name') . ' ' . $this->get_value_from_label('Last Name');
        $mail->AltBody    = $txt_body;
        $mail->MsgHTML($html_body);

        if(!$mail->Send()) {
            echo "There was a problem sending your email.\n";
            echo "Please contact <a href='mailto:{$recipient}?subject=Art%20Scholarship%20Submission%20Error' target=_blank>{$recipient}</a> to confirm that we've received your materials.\n";
        } else {
            echo "Message sent!";
        }
    }
}
?>