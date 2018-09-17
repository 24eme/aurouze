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
        $header->setInitiatingPartyId($this->creditorAccountIBAN); // ID Aurouze ?

        $this->directDebit = TransferFileFacadeFactory::createDirectDebitWithGroupHeader($header, 'pain.008.001.02');

        $date = new \DateTime('now');
        $this->addTransferts($date);

        $this->xml = $this->directDebit->asXML();
        $nameXml = 'prelevements_'.$date->format("Ymd-His");
        file_put_contents(realpath('..').'/data/'.$nameXml.'.xml',$this->xml);
        return $nameXml;

    }

    public function getXml(){
        return $this->xml;
    }


    public function addTransferts($date){
        foreach ($this->factures as $key => $facture) {
            $idPrelevement = $this->createPrelevementId($facture,$date);
            $isFirst = $facture->getSociete()->isFirstPrelevement();
            $seqType = ($isFirst)? PaymentInformation::S_FIRST : PaymentInformation::S_RECURRING;

            $this->directDebit->addPaymentInfo($idPrelevement, array(
                'id'                    => $idPrelevement,
                'dueDate'               => $date,
                'creditorName'          => $this->creditorName,
                'creditorAccountIBAN'   => $this->creditorAccountIBAN,
                'creditorAgentBIC'      => $this->creditorAgentBIC,
                'seqType'               =>$seqType,
                'creditorId'            => $this->creditorId,
                'localInstrumentCode'   => 'CORE'
            ));

            $this->directDebit->addTransfer($idPrelevement, array(
                'amount'                => ''.intval($facture->getMontantAPayer()*100),
                'debtorIban'            => str_ireplace(" ","",$facture->getSepa()->getIban()),
                'debtorBic'             => str_ireplace(" ","",$facture->getSepa()->getBic()),
                'debtorName'            => str_ireplace(" ","",$facture->getSepa()->getNomBancaire()),
                'debtorMandate'         => str_ireplace(" ","",$facture->getSepa()->getRum()),
                'debtorMandateSignDate' => $facture->getSepa()->getDate(),
                'remittanceInformation' => 'FACT '.$facture->getNumeroFacture().' du '.$facture->getDateEmission()->format("d/m/Y").' '. str_ireplace(".",",",sprintf("%0.2f",$facture->getMontantAPayer())),
                'endToEndId'            => 'Aurouze Facture'
            ));
        }
    }

    public function createPrelevementId($facture, $date){
        return 'prelevement_aurouze_'.$facture->getNumeroFacture().'_'.$date->format('Ymd-His');
    }


}
