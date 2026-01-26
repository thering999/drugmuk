<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Contract;
use App\Models\Supplier;

class ContractsController extends Controller {
    public function index() {
        // Optional: Allow public access
        // if (!isset($_SESSION['user_id'])) {
        //     $this->redirect('/login');
        // }

        $contractModel = new Contract();
        $contracts = $contractModel->getAllWithDetails();

        $this->view('contracts/index', ['contracts' => $contracts]);
    }

    public function create() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $supplierModel = new Supplier();
        $suppliers = $supplierModel->all();

        $this->view('contracts/create', ['suppliers' => $suppliers]);
    }

    public function store() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $data = [
            'contract_no' => $_POST['contract_no'],
            'supplier_id' => $_POST['supplier_id'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'total_amount' => $_POST['total_amount'],
            'status' => 'active'
        ];

        $contractModel = new Contract();
        $contractId = $contractModel->create($data);

        // Add contract items if provided
        if (isset($_POST['items']) && !empty($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['drug_id']) && !empty($item['agreed_quantity'])) {
                    $contractModel->addContractItem([
                        'contract_id' => $contractId,
                        'drug_id' => $item['drug_id'],
                        'agreed_price' => $item['agreed_price'] ?? 0,
                        'agreed_quantity' => $item['agreed_quantity']
                    ]);
                }
            }
        }

        $_SESSION['success'] = 'สร้างสัญญาสำเร็จ';
        $this->redirect('/contracts');
    }

    public function show($id) {
        // Optional: Allow public access
        // if (!isset($_SESSION['user_id'])) {
        //     $this->redirect('/login');
        // }

        $contractModel = new Contract();
        $contract = $contractModel->getById($id);
        
        if (!$contract) {
            echo "<h1>ไม่พบสัญญา</h1>";
            echo "<p>ไม่พบสัญญาหมายเลข: " . htmlspecialchars($id) . "</p>";
            echo "<p><a href='/contracts'>← กลับหน้ารายการสัญญา</a></p>";
            return;
        }

        $items = $contractModel->getContractItems($id);
        $this->view('contracts/show', [
            'contract' => $contract,
            'items' => $items
        ]);
    }

    public function edit($id) {
        // Optional: Allow public access
        // if (!isset($_SESSION['user_id'])) {
        //     $this->redirect('/login');
        // }

        $contractModel = new Contract();
        $contract = $contractModel->getById($id);
        
        if (!$contract) {
            echo "<h1>ไม่พบสัญญา</h1>";
            echo "<p>ไม่พบสัญญาหมายเลข: " . htmlspecialchars($id) . "</p>";
            echo "<p><a href='/contracts'>← กลับหน้ารายการสัญญา</a></p>";
            return;
        }

        $supplierModel = new Supplier();
        $suppliers = $supplierModel->all();
        $items = $contractModel->getContractItems($id);

        $this->view('contracts/edit', [
            'contract' => $contract,
            'suppliers' => $suppliers,
            'items' => $items
        ]);
    }

    public function update($id) {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $data = [
            'contract_no' => $_POST['contract_no'],
            'supplier_id' => $_POST['supplier_id'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'total_amount' => $_POST['total_amount'],
            'status' => $_POST['status'] ?? 'active'
        ];

        $contractModel = new Contract();
        $contractModel->update($id, $data);

        // Update contract items
        if (isset($_POST['items']) && !empty($_POST['items'])) {
            // Delete existing items
            $contractModel->deleteContractItems($id);
            
            // Add new items
            foreach ($_POST['items'] as $item) {
                if (!empty($item['drug_id']) && !empty($item['agreed_quantity'])) {
                    $contractModel->addContractItem([
                        'contract_id' => $id,
                        'drug_id' => $item['drug_id'],
                        'agreed_price' => $item['agreed_price'] ?? 0,
                        'agreed_quantity' => $item['agreed_quantity']
                    ]);
                }
            }
        }

        $_SESSION['success'] = 'อัพเดทสัญญาสำเร็จ';
        $this->redirect('/contracts/show/' . $id);
    }

    public function delete($id) {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $contractModel = new Contract();
        $contractModel->delete($id);

        $_SESSION['success'] = 'ลบสัญญาสำเร็จ';
        $this->redirect('/contracts');
    }

    public function expiring() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $days = $_GET['days'] ?? 30;
        $contractModel = new Contract();
        $contracts = $contractModel->getExpiringContracts($days);

        $this->view('contracts/expiring', [
            'contracts' => $contracts,
            'days' => $days
        ]);
    }

    public function suppliers() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $supplierModel = new Supplier();
        $suppliers = $supplierModel->all();

        $this->view('contracts/suppliers', ['suppliers' => $suppliers]);
    }

    public function createSupplier() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'],
                'address' => $_POST['address'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'email' => $_POST['email'] ?? '',
                'contact_person' => $_POST['contact_person'] ?? ''
            ];

            $supplierModel = new Supplier();
            $supplierModel->create($data);

            $_SESSION['success'] = 'เพิ่มผู้จัดจำหน่ายสำเร็จ';
            $this->redirect('/contracts/suppliers');
        }
    }

    public function updateSupplier($id) {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'],
                'address' => $_POST['address'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'email' => $_POST['email'] ?? '',
                'contact_person' => $_POST['contact_person'] ?? ''
            ];

            $supplierModel = new Supplier();
            $supplierModel->update($id, $data);

            $_SESSION['success'] = 'อัพเดทผู้จัดจำหน่ายสำเร็จ';
            $this->redirect('/contracts/suppliers');
        }
    }

    public function deleteSupplier($id) {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $supplierModel = new Supplier();
        $supplierModel->delete($id);

        $_SESSION['success'] = 'ลบผู้จัดจำหน่ายสำเร็จ';
        $this->redirect('/contracts/suppliers');
    }

    public function report() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $contractModel = new Contract();
        $contracts = $contractModel->getAllWithDetails();
        $expiringContracts = $contractModel->getExpiringContracts(30);
        
        $this->view('contracts/report', [
            'contracts' => $contracts,
            'expiringContracts' => $expiringContracts
        ]);
    }
}
