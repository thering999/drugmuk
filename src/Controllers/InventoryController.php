<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Inventory;
use App\Models\Drug;

class InventoryController extends Controller {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $invModel = new Inventory();
        $inventory = $invModel->getAllWithDrugs();

        $this->view('inventory/index', ['inventory' => $inventory]);
    }

    public function receive() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $drugModel = new Drug();
        $drugs = $drugModel->getActiveDrugs();

        $this->view('inventory/receive', ['drugs' => $drugs]);
    }

    public function storeReceive() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $data = [
            'drug_id' => $_POST['drug_id'],
            'lot_no' => $_POST['lot_no'],
            'expire_date' => $_POST['expire_date'],
            'quantity' => $_POST['quantity'],
            'cost_price' => $_POST['cost_price']
        ];

        $invModel = new Inventory();
        $invModel->receive($data);

        $this->redirect('/inventory');
    }
}
