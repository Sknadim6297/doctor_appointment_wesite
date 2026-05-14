<!DOCTYPE html>
<html lang="en">

<head>
 <style>

table {
    border-collapse: collapse;
}

#agent_name
{
	margin-left:80%;
}

#tbl1,#tbl2,#tbl3,#tbl4{
    border-collapse: collapse;
	
}
#tbl1 {
    border: 4px solid black;
}
#tbl1 td{
    border: 4px solid black;
}
#mytable {
    transform:rotate(90deg);
   // margin-top:100%;

}
#conn
{
	border: 1px solid black;
	width:35%;
	height: 8%;
	margin-left: 37%;
}

 </style>

</head>

<body>


<table align="right" style="padding-left: 5%;margin-top:80px;">

	<tr>
		<td align="center">
		<p>
				<p>
 				<b><span  style="font-family: Arial Black,Arial Bold,Gadget,sans-serif;font-size:17px">MEDICAL DEFENCE FORUM (MEDEFORUM)</span><br></b>
 				<span style="font-family: Arial,Helvetica Neue,Helvetica,sans-serif;font-size:15px">
						MEDICAL DEFENCE FORUM OF INDIA,<br>
    					1 S.P MUKHERJEE ROAD<br>KOLKATA-700028 WEST BENGAL<br>
    					PHONE: (33) 60503303  FAX: 60503303
     					<br>
    					EMAIL: info@medeforum.com<br><br>
    					<span style="font-family: Arial Black,Arial Bold,Gadget,sans-serif;font-weight: bold;font-size:15px;">
    					PROFESSIONAL INDEMNITY MEMBERSHIP<BR>
    					MEMBERSHIP<BR>NO.:&nbsp;&nbsp;
     					<?php echo $customer_id;?></span>
						
				</p>
			</td>
		</tr>

		<tr>
			<td align="center">
			<div id="conn" style="font-size:12px;font-family:'Calibri (Body)'">

			<?php // if($payment_mode == "monthly_plan"){ ?>
    		<span> PERIOD OF MEMBERSHIP<BR />From <?php echo date('H:i');?> hrs of <?php echo date('d/m/Y', strtotime( $enrollment_date ));?><br />to midnight of 
			<?php 	



                //$renewal_date = date(strtotime('-1 day', $renewal_date));
            
                $renewal_date = date('Y-m-d',(strtotime ( '-1 day' , strtotime ( $renewal_date) ) ));
                $renewal_date = date('d/m/Y', strtotime( $renewal_date ));
			    echo $renewal_date;

			?>
    		</span>
    		<?php // } ?>
   			
		     
    		</div>
    		</td>
		</tr>
	<tr>
		<td align="center"> 
    		<br><br><span style="font-size:12px;font-family:'Cambria(headings)';"><i>Member</i></span>   <br>                              
    		<span  style="font-family: Arial Black,Arial Bold,Gadget,sans-serif;font-size:18px"><b><?php echo $doctor_name;?></b></span><br>

            <!-- <span  style="font-family: Arial Black,Arial Bold,Gadget,sans-serif;font-size:18px"><b><?php echo $doctor_mobile_no;?></b></span><br> -->

            <span style="font-family: Arial,Helvetica Neue,Helvetica,sans-serif;font-size:18px;">Contact No:<?php echo $doctor_mobile_no; ?></span><br>

    			<span style="font-family: Arial,Helvetica Neue,Helvetica,sans-serif;font-size:18px;">
    			<?php echo wordwrap($doctor_address,25,"<br>").",".$state.","."<br>".$city.",".$postcode;?><br></span><br>
        </td>
	</tr>

	<tr>
		<td style="padding-left:25%">
		<span style="font-family:'Cambria(headings)';font-size:11px;">
		Agent Name:<?php echo $agent_name;?><br>
		Agent Code:<br>
		Mobile/Landline:<br>
		Number/E mail:<?php echo $agent_phone_no;?></span><br>		</td>
	</tr>
    <tr>
    <td style="padding-left:20%"><br><br><br><br><span style="font-size:12px;font-family:'Cambria(headings)';"><?php echo url('/');?>.....<?php echo date('d/m/Y');?></span></td>
    </tr>
</p>
</table> 
<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
<table style="margin-top: 20%;" align="left">
<tr>
<td></td>
</tr>

</table>
<table style="margin-top: 20%;" align="right">
<tr>
<td></td>
</tr>

</table>

<!--2nd page-->
<span style="font-family: Arial Black,Arial Bold,Gadget,sans-serif; font-size: 15px;padding-left: 3%">
<B>LEGAL SERVICE MEMBERSHIP SCHEDULE</B></span><br><br>
<span style="font-size:11px;font-family:'Calibri (Body)'">
<table border="1" align="left" width="300" id="tbl1" >
<tr>
	<td >Membership Number</td>
	<td><?php echo $customer_id;?></td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>

<tr>
	<td>Membership Details</td>
	<td>Name</td>
	<td colspan="2"><?php echo $doctor_name; ?></td>

	
</tr>
<tr>
	<td></td>
	<td>Tel No.</td>
	<td><?php echo $doctor_mobile_no; ?></td>
	<td>&nbsp;</td>
	
	
</tr>

<tr>
	<td>Legal service</td>
	<td><?php 
			if($plan_id == 1)
			{
				echo "INR".$coverage_id."00000";
			}
			else if($plan_id == 2)
			{
				echo "INR".$coverage_id."00000";
			}
			else if($plan_id == 3)
			{
				echo "AS PER <BR>INSURANCE T/C";
			} 
		?>
	</td>
	<td>SCHEME </td>
	<td><?php echo $plan_name." PLAN"; ?></td>
</tr>

<tr>
	<td>Period of membership</td>
	<td>From <br>(<?php 
		      echo $membership_period;
			?> )</td>

    <td><?php echo date('H:i');?> hrs of <br> <?php echo date('d/m/Y', strtotime( $enrollment_date ));?></td>
    <td>
    		to midnight of <?php  echo $renewal_date ;  ?>
    </td>
    
                                        
	
	
</tr>



<tr>
	<td>Limit of legal Service </td>
	<td><?php 
			if($plan_id == 1)
			{
				echo "INR".$coverage_id."00000";
			}
			else if($plan_id == 2)
			{
				echo "INR".$coverage_id."00000";
			}
			else if($plan_id == 3)
			{
				echo "AS PER <BR>INSURANCE T/C";
			} 
		?></td>
	<td>Max Limit of Compensation </td>
	<td><?php if($plan_id == 3)
    {
        echo $coverage_id."00000";
    }
    ?></td>
</tr>
<tr>
	<td>STATE </td>
	<td><?php echo $state; ?></td>
	<td>PINCODE</td>	
	<td><?php echo $postcode; ?></td>
</tr>

<tr>
	<td>Registration No. </td>
	<td><?php echo $medical_reg_no; ?></td>
	<td>Registration year</td>
	
	<td><?php echo $year_of_reg ; ?></td>
</tr>
<tr>
	<td>District Forum</td>
	<td>COV</td>
	<td></td>
	<td></td>
</tr>
<tr>
	<td>State Forum</td>
	<td>COV</td>
	<td></td>
	<td></td>
</tr>
<tr>
	<td>National Forum</td>
	<td>COV</td>
	<td></td>
	<td></td>
</tr>
<tr>
	<td>Line of specialisation </td>
	<td><?php echo $speciliazition_name;?></td>
	<td></td>
	<td></td>
</tr>


<tr>
	<td>Qualification</td>
	<td><?php echo strtoupper($doctor_qualification);?></td>
	<td>Qualification year</td>
	<td><?php echo $doctor_qualification_year;?></td>
</tr>
<!--<tr>
	<td>Total Subscription</td>
	<td>INR <?php /*echo $amount; */?></td>
	<td>Discount Amt-</td>
	<?php /*if($payment_mode == "monthly_plan"){ */?>
    <td>NIL</td>   
    
	<?php /*} else if($payment_mode == "yearly_plan"){  */?>
    
    <td><?php /*echo "NIL";*/?></td>
    
	<?php /*} else if($payment_mode == "two_year"){ */?>
    <td><?php /*echo "10%";*/?></td>
    
	<?php /*} else if($payment_mode == "three_year"){ */?>
    <td><?php /*echo "15%";*/?></td>
    
    <?php /*} else if($payment_mode == "four_year"){  */?>
    <td><?php /*echo "20%";*/?></td>
    
    <?php /*} else if($payment_mode == "five_year"){ */?>
    <td><?php /*echo "25%";*/?></td>
    
    <?php /*} */?>
	
</tr>
--></table>
</span>


<table align="right">
<tr>
<td>

<p style="padding-left:55%;font-size:13px;margin-top: 3%">
 <span style="font-size:11px;font-family:'Calibri (Body)'">
Respected Member</span><br>
<span style="font-size:10px;font-family:'Calibri (Body)'">



Thank you for being our member of MEDEFORUM LEGAL SERVICES.<br> We are offering you the following service under

<br>Terms and condition applied by Medeforum. As you are a member of our organization, your profession

 Is under legal service agreement with us.<br>

 a)Legal defence cost paid by Medeforum as per your membership bond.<br>    

 b)Medeforum legal service include district, state, consumer forum.<br>

 c)Medeforum legal service include civil court, human right commission, criminal court and medical council 

   of India & Optometries<br>

   TERMS AND CONDITION:<br></span>



 1)    <span style="font-family: Arial Black,Arial Bold,Gadget,sans-serif; font-size: 9px;"><b>HOW TO SUBMIT A MEDICO LEGAL CLAIM WITH MEDEFORUM:</b><br>



 a)   Original membership certificate for the period of treatment for which patient made a <br>complaint against 

      You.<br>

 b)   Letter to Medeforum describing the entire treatment done by the Member for <br>which complain made against

      You.<br>

 c)   Vakaulatnama sign by the Member for authorising the lawyer to defend the case in the court.<br>

 d) Any previous case arising before the membership period is not under the service. <br>Membership period means

    The period commencing from the effective date and hour as shown in the membership schedule and       

    terminating  At midnight on expiry date as even on your membership schedule.

    <br>   The forum does not assume any financial liability<br><br><br></span>

    <span style="font-family: Arial Black,Arial Bold,Gadget,sans-serif; font-size: 10px;">

    2) <b>Forum does not cover any service under the circumstances given below:-</b><br>

    b) Service rendered while influence of intoxicants or narcotics. <br>

    c) Third party public liability.<br>

    d) claims made against the member arising from the performance of cosmetic plastic surgery, 

       Hair transplant or any  beautification.<br>

    e) Claim arising from any condition directly or indirectly caused by or associated human t-cell lymph 

       Tropic, virus type iii (HTLVIII) or lymphadenopathy associated virus (lav) or the mutants

       Derivatives or variation thereof or in any way related to acquired immune   deficiency syndrome or any 

       Condition of similar kind howsoever it may be named.<br>

    f) Assume by the membership by agreement and which would not have attached in the Absence

       Of such agreement<br>

    g) Arising out of deliberate, wilful or intentional non compliance of any statutory provision.<br>

    h) Arising out of all personal injuries such as libel slander, flash arrest, wrongful

       Eviction, wrongful Detention    defamation etc and mental injury anguish or shock.<br>

    i)  Arising out of fines penalties, punitive or exemplary damages.<br> </span>

</p>

    </td>
</tr>    

   </table> 
   
<table style="margin-top: 70%">
<tr>
<td colspan="4"><span style="font-size:11px;font-family:'Calibri (Body)'">
For medical defence forum of India</span><br>
 <b><span style="font-size:13px;font-family:'Calibri (Body)'">
Authorised signatory</b></span><br>
<span style="font-size:11px;font-family:'Calibri (Body)'">

<?php echo date('d/m/Y');?> by Medeforum Kolkata division<br> 
as per the terms  and condition of medical defence forum of India </td></span>
</tr>

</table>


 <!--<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br> -->
<br><br><br><br><br>
<div style="align:right">
 <center>
 		<h3><span style="font-family: Arial Black,Arial Bold,Gadget,sans-serif; font-size: 19px;">
  			MEDICAL DEFENCE FORUM (MEDEFORUM) <br>
            MEDEFORUM MONEY RECEIPT</span>
        </h3>
</center>
<table border="1" id="tbl1" align="center" width="80%" style="font-size: 13px;font-size:14px;font-family:'Calibri (Body)'">

    <tr>
       <td rowspan="2" style="vertical-align:text-top;"><b>Issuing Office<br> code/Address :</b></td>
       <td rowspan="2">MEDEFORUM <br>MEDICAL DEFENCE FORUM OF INDIA,<br>
       				   1 S.P MUKHERJEE ROAD KOLKATA-700028 WEST BENGAL<br><br><br>
       </td>
       <td>Receipt<br>Number :</td>
       <td><?php echo $recipet_no; ?></td>
    </tr>
    
    <tr>
    	<td>Collection <br>Date : </td>
    	<td>
			<?php
			if($payment_method == '1')
			{
                $cheque_rec_date = date('d/m/Y', strtotime( $cheque_rec_date ));
				echo $cheque_rec_date;
			}
			else
			{
                $cash_rec_date = date('d/m/Y', strtotime( $cash_rec_date ));
				echo $cash_rec_date;
			}
			?></td>
    </tr>
</table>

<p  style="padding-left:15%;width: 80%;font-size:14px;font-family:'Calibri (Body)'">
Received with thanks from <?php echo $doctor_name; ?> (Customer ID : <?php echo $customer_id;?>) a 
<?php
function convert_number_to_words($number) {

    $hyphen      = '-';
    $conjunction = ' and ';
    $separator   = ', ';
    $negative    = 'negative ';
    $decimal     = ' point ';
    $dictionary  = array(
        0                   => 'zero',
        1                   => 'one',
        2                   => 'two',
        3                   => 'three',
        4                   => 'four',
        5                   => 'five',
        6                   => 'six',
        7                   => 'seven',
        8                   => 'eight',
        9                   => 'nine',
        10                  => 'ten',
        11                  => 'eleven',
        12                  => 'twelve',
        13                  => 'thirteen',
        14                  => 'fourteen',
        15                  => 'fifteen',
        16                  => 'sixteen',
        17                  => 'seventeen',
        18                  => 'eighteen',
        19                  => 'nineteen',
        20                  => 'twenty',
        30                  => 'thirty',
        40                  => 'fourty',
        50                  => 'fifty',
        60                  => 'sixty',
        70                  => 'seventy',
        80                  => 'eighty',
        90                  => 'ninety',
        100                 => 'hundred',
        1000                => 'thousand',
        1000000             => 'million',
        1000000000          => 'billion',
        1000000000000       => 'trillion',
        1000000000000000    => 'quadrillion',
        1000000000000000000 => 'quintillion'
    );

    if (!is_numeric($number)) {
        return false;
    }

    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        // overflow
        trigger_error(
            'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
            E_USER_WARNING
        );
        return false;
    }

    if ($number < 0) {
        return $negative . convert_number_to_words(abs($number));
    }

    $string = $fraction = null;

    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }

    switch (true) {
        case $number < 21:
            $string = $dictionary[$number];
            break;
        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $units  = $number % 10;
            $string = $dictionary[$tens];
            if ($units) {
                $string .= $hyphen . $dictionary[$units];
            }
            break;
        case $number < 1000:
            $hundreds  = $number / 100;
            $remainder = $number % 100;
            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
            if ($remainder) {
                $string .= $conjunction . convert_number_to_words($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= convert_number_to_words($remainder);
            }
            break;
    }

    if (null !== $fraction && is_numeric($fraction)) {
        $string .= $decimal;
        $words = array();
        foreach (str_split((string) $fraction) as $number) {
            $words[] = $dictionary[$number];
        }
        $string .= implode(' ', $words);
    }

    return $string;
}
echo "sum of<br> Rs.".$amount.".00"."(".strtoupper(convert_number_to_words($amount)).")as per detail given here under"; 
?> 
</p>

<table border="1px" id="tbl2" align="center" width="80%" style="font-size: 13px;font-size:14px;font-family:'Calibri (Body)'">
   
      <tr>
        <th>SL <br>NO</th>
        <th>Membership  Number</th>
        <th>Membership Type</th>
        <th>Particulars</th>
        <th>Total Amount</th>
      </tr>
  
  
  
      <tr>
        <td align="center">1.</td>
        <td><?php echo $customer_id;?></td>
        <td>PROFESSIONAL INDEMNITY<br><?php echo strtoupper($plan_name)." PLAN";?></td>
        <td>TOTAL SUBCRIPTION FOR <br><?php echo strtoupper($payment_mode); ?></td>
        <td><?php echo $amount.".00";?></td>
 	</tr>
 	<tr>
        <td align="center">2.</td>
        <td><?php echo $customer_id;?></td>
        <td>LEGAL SERVICE<br><?php echo strtoupper($plan_name)." PLAN";?></td>
        <td>
        	<?php 
			if($plan_id == 1)
			{
				echo "TOTAL SUBCRIPTION<br>".$coverage_id."00000";
			}
			else if($plan_id == 2)
			{
				echo "TOTAL SUBCRIPTION<br>".$coverage_id."00000";
			}
			else if($plan_id == 3)
			{
				echo "AS PER <BR>INSURANCE T/C";
			} 
		?>					
        
        <td>0.00</td>
 	</tr>

  </table>
  
<table align="center" style="padding-left: 14%;font-size:14px;font-family:'Calibri (Body)'" >
	<tr>
 		<td>Total (Rounded Off)</td>
 		<td>:</td>
 		<td><?php echo $amount.".00";?></td>
 	</tr>
 	<tr>
 		<td>Stamp Duty:</td>
 		<td>:</td>
		 <td>0.00</td>
	</tr>
	<tr>
		 <td>Bank Charges:</td>
		 <td>:</td>
 		<td>0.00</td>
	 </tr>
	<tr>	
		 <td>Total Amount: </td>
		 <td>:</td>
		 <td><?php echo $amount.".00";?></td>
	 </tr>

</table>
<table border="1"  id="tbl4" align="center" width="80%" style="font-size: 13px;">
<?php if($payment_method == '1')
{
?>
    <tr>
    <td colspan="8"><span style="font-family: Arial Black,Arial Bold,Gadget,sans-serif; font-size: 17px;"><b>Instrument Detail</b></span></td>
    </tr>
    <tr>
    <td>SL<br>NO</td>
    <td>Payment ID</td>
    <td>Mode of <br>Payment</td>
    <td>Instrument <br>Number</td>
    <td>Instrument <br>Date</td>
    <td>Bank<br>Name</td>
    <td>Branch<br>Name</td>
    <td>Tagged<br> Amount</td>
    </tr>
    <tr>
    <td>1.</td>
    <td><?php echo $recipet_no;?></td>
    <td> <?php if($payment_method == '1'){echo "CHEQUE";}else if($payment_method == '2'){echo "CASH";}?></td>
    <td><?php echo $cheque_no;?></td>
    <td><?php  /*$cheque_rec_date = date('d/m/Y', strtotime( $cheque_rec_date ));*/
                echo $cheque_rec_date; ?></td>
    <td><?php echo $bank_name;?></td>
    <td><?php echo $branch_name;?></td>
    <td><?php echo $amount;?></td>
    </tr>
<?php
}
else
{
?>
<tr>
    <td colspan="5"><span style="font-family: Arial Black,Arial Bold,Gadget,sans-serif; font-size: 17px;"><b>Cash Detail</b></span></td>
    </tr>
    <tr>
    <td>SL<br>NO</td>
    <td>Payment ID</td>
    <td>Mode of <br>Payment</td>
   
    <td>Cash <br>Date</td>
   
    <td>Total<br> Amount</td>
    </tr>
    <tr>
    <td>1.</td>
    <td><?php // echo $recipet_no;
       echo $recipet_no;
    ?></td>
    <td> <?php if($payment_method == '1'){echo "CHEQUE";}else if($payment_method == '2'){echo "CASH";}?></td>  
    <td><?php

     echo $cash_rec_date;
     ?></td>
    <td><?php echo $amount;?></td>
    </tr>
<?php
}
?>
</table>
</center>
<br><br>
<table  align="left" style="padding-left: 10%;font-size:13px;font-family:'Calibri (Body)'">
   
      <tr>
        <td> For MEDEFORUM </td>
      </tr>
      <tr>
        <td>MEDICAL DEFENCE FORUM </td>
      </tr>
      <tr>
        <td> Authorised Signatory   </td>
      </tr>
      <tr>
        <td><?php echo date('d/m/Y'); ?> by Medeforum Legal division</td>
      </tr>
</table>
<div style="margin-left: 50%;font-size:13px;font-family:'Calibri (Body)'">
<table>      

  
  <tr>
        <td> as per the terms and condition of medical defence forum of India  </td>
 </tr>
 <tr>
        <td>Cashier Initial </td>
 </tr>
 <tr>
        <td>Note: <br>
        1.	Receipt valid subject to realisation of cheque<br>
        2.	Please quote membership no. , collection date.</td>

 </tr>    
 <tr>
 <td style="margin-left: 5%"> And date in all correspondences.</td>
 </tr>
   
  </table> 
</div>
</body>    






    
        
                   
