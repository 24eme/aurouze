<?php

namespace AppBundle\Tool;

use Digitick\Sepa\DomBuilder\DomBuilderFactory;
use Digitick\Sepa\GroupHeader;
use Digitick\Sepa\TransferFile\CustomerCreditTransferFile;
use Digitick\Sepa\TransferInformation\CustomerCreditTransferInformation;
use Digitick\Sepa\PaymentInformation;
use Digitick\Sepa\DomBuilder\CustomerCreditTransferDomBuilder;
use Digitick\Sepa\TransferFile\Factory\TransferFileFacadeFactory;


class PrelevementXml {

protected $banqueParameters;
protected $creditorId;
protected $creditorName;
protected $creditorAccountIBAN;
protected $creditorAgentBIC;

protected $factures;
protected $directDebit;
protected $xml;

    public function __construct($factures,$banqueParameters)
    {
        $this->factures = $factures;
        $this->banqueParameters = $banqueParameters;
        $this->creditorId = $banqueParameters['creditorId'];
        $this->creditorName = $banqueParameters['creditorName'];
        $this->creditorAccountIBAN = $banqueParameters['creditorAccountIBAN'];
        $this->creditorAgentBIC = $banqueParameters['creditorAgentBIC'];

    }

    public function createPrelevement(){
        $header = new GroupHeader(date('Y-m-d-H-i-s'), 'Aurouze');
        $header->setInitiatingPartyId($this->creditorAccountIBAN);

        $this->directDebit = TransferFileFacadeFactory::createDirectDebitWithGroupHeader($header, 'pain.008.001.02');

        $date = new \DateTime('now');
        $this->addTransferts($date);

        $this->xml = $this->directDebit->asXML();
        $nameXml = 'prelevements_'.$date->format("Ymd-His");
        $path = realpath(__DIR__.'/../../..').'/data/'.$nameXml.'.xml';
        file_put_contents($path, $this->xml);
        return $path;

    }

    public function getXml(){
        return $this->xml;
    }


    public function addTransferts($date){
      $tabPmtfInf = [];
      foreach( $this->factures as $key => $facture){
        $d = $facture->getPrelevementDate();
        $tabPmtfInf[$d->format('YmdHis')][] = $facture;
      }

      $seqType = PaymentInformation::S_RECURRING;
      foreach($tabPmtfInf as $d => $tabFactures){
        $this->directDebit->addPaymentInfo($d, array(
            'id'                    => $d,
            'dueDate'               => $tabPmtfInf[$d][0]->getPrelevementDate(), //recuperer la premiere facture de la date prise
            'creditorName'          => $this->creditorName,
            'creditorAccountIBAN'   => $this->creditorAccountIBAN,
            'creditorAgentBIC'      => $this->creditorAgentBIC,
            'seqType'               => $seqType,
            'creditorId'            => $this->creditorId,
            'localInstrumentCode'   => 'CORE'
        ));

        foreach ($tabFactures as $key => $facture) {

            $facture->setInPrelevement($facture->getPrelevementDate());

            $this->directDebit->addTransfer($d, array(
                'amount'                => ''.$facture->getMontantAPayer()*100,
                'debtorIban'            => str_ireplace(" ","",$facture->getSepa()->getIban()),
                'debtorBic'             => str_ireplace(" ","",$facture->getSepa()->getBic()),
                'debtorName'            => $facture->getSepa()->getNomBancaire(),
                'debtorMandate'         => str_ireplace(" ","",$facture->getSepa()->getRum()),
                'debtorMandateSignDate' => $facture->getSepa()->getDate(),
                'remittanceInformation' => 'FACT '.$facture->getNumeroFacture().' du '.$facture->getDateEmission()->format("d m Y").' '. str_ireplace(array(".",","),"EUR",sprintf("%0.2f",$facture->getMontantAPayer())),
                'endToEndId'            => 'Aurouze Facture'
            ));
        }
      }

    }

    public function createPrelevementId($facture, $date){
        return $facture->getNumeroFacture().'_'.$date->format('YmdHis');
    }


}
