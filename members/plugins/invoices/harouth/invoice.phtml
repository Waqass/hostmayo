<?php if ($this->invoicelogo == "") {
    $logoclass = "invoice-label-no-logo";
} else {
    $logoclass = "";
} ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html >
    <head >
        <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
    </head>
    <body>

        <?php //$this->status; ?>

        <div class="invoice-header <?php echo $logoclass;?>">

            <?php if ($this->invoicelogo != "") { ?>
                <div class="invoice-logo">
                    <img id="logo" alt="logo" border="0" src="<?php echo $this->invoicelogo; ?>" />
                </div>
            <?php } else { ?>
                <div class="invoice-no-logo"></div>
            <?php } ?>

            <div class="invoice-label <?php echo $logoclass;?>">
                <b><span class="invoice-label-text"><?php echo $this->invoice; ?></span>  #<?php echo $this->invoiceNum; ?></b><br/>
            </div>

            <div class="invoice-dates">
                <?php
                    if (trim($this->duedate) != "") {
                        echo $this->user->lang("Due Date").": ".$this->duedate."<br>";
                    }
                    if (trim($this->paidDate) != "") {
                        echo $this->user->lang("Paid").": ".$this->unescape($this->paidDate)."<br>";
                    }
                    if (trim($this->paymentMethod) != "") {
                        echo $this->paymentLabel.": ".$this->paymentMethod."<br>";
                    }
                    if (trim($this->pmtRef) != "") {
                        echo $this->user->lang('Pmt Reference').": ".$this->pmtRef."<br>";
                    }
                ?>
            </div>

            <table class="address-block">
                <tr style="width: 100%">
                    <td width="50%" valign="top">
                        <div class="company-address-block">
                            <?php
                                echo "<span class='customer-name'>".$this->companyName."</span><br>";
                                if (trim($this->companyAddress) != "") {
                                    echo nl2br($this->companyAddress)."<br/>";
                                }
                                if (trim($this->companyEmail) != "") {
                                    echo "<a class='company-email' href='mailto:".$this->companyEmail."'>".$this->companyEmail."</a><br/>";
                                }
                            ?>
                        </div>
                    </td>
                    <td width="50%" align="right" valign="top">
                        <?php
                            echo "<span class='customer-name'>";
                            if (trim($this->customerOrg) != "") {
                                echo $this->customerOrg."<br/>";
                            }
                            if (trim($this->customerName) != "") {
                                echo $this->customerName."<br/>";
                            }
                            echo "</span>";
                            if (trim($this->customerAddress) != "") {
                                echo $this->unescape($this->customerAddress)."<br/>";
                            }
                            if (trim($this->customerPhone) != "") {
                                echo $this->customerPhone."<br/>";
                            }
                            if (trim($this->customerEmail) != "") {
                                echo $this->customerEmail."<br/>";
                            }

                        ?>
                    </td>
                </tr>
            </table>

            <table class="invoice-table-header">
                <tr>
                    <?php foreach ( $this->invoiceheaders as $header ) { ?>
                        <td width="<?php echo $header['width']; ?>" align="<?php echo $header['align']; ?>" >
                            <span><?php echo $header['text']; ?></span>
                        </td>
                    <?php } ?>
                </tr>
            </table>

        </div>

        <table class="invoice-entries">
            <?php foreach ( $this->invoiceEntries as $invoiceEntry ) {
                $placement = ($placement == "odd") ? "even" : "odd";
            ?>
            <tr class="<?php echo $placement;?>">
                <?php foreach ( $invoiceEntry['data'] as $data ) { ?>
                    <td valign="top" width="<?php echo $data['width']; ?>" align="<?php echo $data['align']; ?>">
                        <?php if (is_array($data['data'])) { ?>
                            <span class="invoice-entry-description"><?php echo $data['data'][0];?></span>
                            <span class="invoice-entry-details">
                            <?php
                                if ($data['data'][1] != "") {
                                    echo "<br/>".$data['data'][1];
                                }
                            ?>
                            </span>
                        <?php } else {
                            echo $data['data'];
                         } ?>
                    </td>
                <?php } ?>
            </tr>
            <?php } ?>
        </table>

        <table class="invoice-total">
            <?php foreach ( $this->totalLabels as $total ) { ?>
            <tr>
                <td align="right" width="595">
                    <b><?php echo $total['totalLabel']; ?></b>
                </td>
                <td align="right" width="105" <?php echo $total['colspan']; ?>>
                    <b><?php echo $total['totalPrice']; ?></b>
                </td>
            </tr>
            <?php } ?>
        </table>

        <div class='invoice-transactions'><?php echo $this->unescape($this->pmtSuccessfulTransactions); ?></div>

        <div class="invoice-footer">
        <?php if ($this->vatNumber != "") { ?>
            <p><b><?php echo $this->user->lang('VAT Number'); ?></b> <?php echo $this->vatNumber; ?></p>
        <?php } ?>

        <?php if ($this->additionalnotes != "") { ?>
            <p>
                <b><?php echo $this->user->lang("Additional Notes");?></b><br/>
                <?php echo nl2br($this->unescape($this->additionalnotes)); ?>
            </p>
        <?php } ?>

        <?php
            echo "<p>".nl2br($this->footerContent)."</p>";
            if ($this->disclaimerContent != "") {
                echo "<hr>";
                echo "<small>".nl2br($this->disclaimerContent)."</small>";
            }
        ?>
        </div>

    </body>
</html>