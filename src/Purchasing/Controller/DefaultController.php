<?php

namespace Purchasing\Controller;

use Sales\Model\ShippingModel;
use Core\Model\ErrorModel;
use Sales\Model\OrderModel;
use Company\Model\CompanyModel;
use Finance\Model\FinanceModel;

class DefaultController extends \Core\Controller\DefaultController {

    protected $_shippingModel;

    public function orderAction() {
        if (ErrorModel::getErrors()) {
            return $this->_view;
        }
        $order_id = $this->params()->fromQuery('id');
        $orderModel = new OrderModel();
        $orderModel->initialize($this->serviceLocator);
        $this->_view->orderStatus = $orderModel->getAllOrderStatus();

        if ($order_id) {
            $companyModel = new CompanyModel();
            $companyModel->initialize($this->serviceLocator);
            $order = $orderModel->getPurchasingOrder($order_id);
            $this->_view->order = $order;
            $this->_view->quotes = $orderModel->getAllQuotesFromOrder($order_id);
            $this->_shippingModel = new ShippingModel();
            $this->_shippingModel->initialize($this->serviceLocator);
            $this->_view->quote = $this->_view->quotes ? $this->_view->quotes[0] : $order->getQuote();
            $this->_view->companyAddress = $this->_shippingModel->getCompanyOriginAddressByCity($this->_view->quote->getCityOrigin());
            $this->_view->companyContacts = $this->_shippingModel->getCompanyContacts();
            $this->_view->quoteCompanyOriginContact = $order->getRetrieveContact() ? : ($this->_view->companyContacts ? $this->_view->companyContacts[0] : NULL);
            $this->_view->quoteCompanyDestinationContact = $order->getDeliveryContact() ? : ($this->_view->companyContacts ? $this->_view->companyContacts[0] : NULL);
            $this->_view->quoteCompanyOriginPeople = $order->getRetrievePeople();
            $this->_view->quoteCompanyDestinationPeople = $order->getDeliveryPeople();
            $this->_view->quoteCompanyOriginAddress = $order->getAddressOrigin() ? : ($this->_view->companyAddress ? $this->_view->companyAddress[0] : NULL);
            $this->_view->quoteCompanyDestinationAddress = $order->getAddressDestination() ? : ($this->_view->companyAddress ? $this->_view->companyAddress[0] : NULL);
            $this->_view->clientAddress = NULL;
            $this->_view->clientContacts = NULL;
            $this->_view->quoteClientOriginPeople = $order->getRetrievePeople();
            $this->_view->quoteClientDestinationPeople = $order->getDeliveryPeople();
            $this->_view->quoteClientOriginContact = $order->getRetrieveContact() ? : ( $this->_view->clientContacts ? $this->_view->clientContacts[0] : NULL);
            $this->_view->quoteClientDestinationContact = $order->getDeliveryContact() ? : ($this->_view->clientContacts ? $this->_view->clientContacts[0] : NULL);
            $this->_view->quoteClientOriginAddress = $order->getAddressOrigin() ? : ($this->_view->clientAddress ? $this->_view->clientAddress[0] : NULL);
            $this->_view->quoteClientDestinationAddress = $order->getAddressDestination() ? : ($this->_view->clientAddress ? $this->_view->clientAddress[0] : NULL);
            $this->_view->order = $orderModel->getPurchasingOrder($order_id);
            $this->_view->client_invoice_tax = $orderModel->getClientInvoiceTaxFromOrder($this->_view->order);
            $this->_view->carrier_invoice_tax = $orderModel->getCarrierInvoiceTaxFromOrder($this->_view->order);

            if ($this->_view->client_invoice_tax && $this->_view->client_invoice_tax->getInvoice()) {
                $nf = simplexml_load_string($this->_view->client_invoice_tax->getInvoice());
                $this->_view->invoice_tax_number = $orderModel->discoveryInvoiceNumber($this->_view->client_invoice_tax);
                $this->_view->invoice_tax_price = $nf->NFe->infNFe->total->ICMSTot->vNF;
            }
            if ($this->_view->carrier_invoice_tax && $this->_view->carrier_invoice_tax->getInvoice()) {
                $this->_view->carrier_invoice_tax_number = $orderModel->discoveryInvoiceNumber($this->_view->carrier_invoice_tax);
                $orderModel->discoveryOrderFromInvoiceTax($this->_view->carrier_invoice_tax);
            }
            if ((count($this->_view->order->getInvoice()) > 0 && $this->_view->order->getStatus()->getStatus() == 'waiting payment' && $this->_view->order->getClient()->getId() != $companyModel->getCompanyDomain()->getPeople()->getId()) || ($order && count($order->getInvoice()) > 0 && $order->getInvoice()[0]->getInvoice()->getStatus()->getStatus() == 'waiting payment')) {
                $this->_view->itauData = $orderModel->getItauDataFromOrder($this->_view->order);
                $this->_view->itauReturn = $orderModel->verifyPayment($this->_view->order);
            }
            if (count($this->_view->order->getInvoice()) > 0) {
                $this->_view->itauStatusData = $orderModel->getItauStatusDataFromOrder($this->_view->order);
                $financeModel = new FinanceModel();
                $financeModel->initialize($this->serviceLocator);
                $this->_view->saleOrders = $financeModel->getSalesOrdersFromPurchasingOrder($this->_view->order->getId());
                
            }

            $this->_view->setTemplate('purchasing/default/order.phtml');
        } else {
            $this->_view->selectedStatus = $this->params()->fromQuery('order-status');
            $this->_view->purchasingOrders = $orderModel->getPurchasingOrders($this->params()->fromQuery());
            $this->_view->setTemplate('purchasing/default/order-list.phtml');
        }
        return $this->_view;
    }

}
