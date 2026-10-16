<?php
session_start();

/* ==============================
   CHECK ADMIN LOGIN
   ============================== */

if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {
    header("Location: login.html");
    exit();
}

$admin_username = $_SESSION["admin_username"] ?? "Admin";
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Clients - Loan Management System</title>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css"
    >

    <style>

        /* =========================
           RESET
        ========================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7fb;
            color: #222;
        }


        /* =========================
           LAYOUT
        ========================= */

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }


        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            width: 260px;

            background: linear-gradient(
                180deg,
                #003b73,
                #005fc5
            );

            color: white;

            position: fixed;

            top: 0;
            bottom: 0;
            left: 0;

            overflow-y: auto;
        }


        .sidebar-logo {
            padding: 25px 20px;

            text-align: center;

            border-bottom:
                1px solid rgba(255,255,255,0.15);
        }


        .logo-icon {
            width: 65px;
            height: 65px;

            background: white;

            color: #006edc;

            border-radius: 50%;

            margin: 0 auto 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 28px;
        }


        .sidebar-logo h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }


        .sidebar-logo p {
            font-size: 12px;
            opacity: 0.8;
        }


        /* =========================
           MENU
        ========================= */

        .sidebar-menu {
            padding: 20px 12px;
        }


        .menu-title {
            font-size: 11px;

            text-transform: uppercase;

            opacity: 0.6;

            margin: 10px;

            letter-spacing: 1px;
        }


        .sidebar-menu a,
        .logout-section a {

            display: flex;

            align-items: center;

            color: white;

            text-decoration: none;

            padding: 13px 15px;

            margin-bottom: 5px;

            border-radius: 8px;

            transition: 0.3s;

            font-size: 14px;
        }


        .sidebar-menu a i,
        .logout-section a i {

            width: 30px;

            font-size: 17px;
        }


        .sidebar-menu a:hover,
        .logout-section a:hover {

            background:
                rgba(255,255,255,0.15);
        }


        .sidebar-menu a.active {

            background: white;

            color: #005fc5;
        }


        /* =========================
           LOGOUT
        ========================= */

        .logout-section {

            padding: 15px 12px;

            border-top:
                1px solid rgba(255,255,255,0.15);
        }


        .logout-section a:hover {

            background:
                rgba(255,0,0,0.25);
        }


        /* =========================
           MAIN CONTENT
        ========================= */

        .main-content {

            margin-left: 260px;

            width:
                calc(100% - 260px);

            min-height: 100vh;
        }


        /* =========================
           HEADER
        ========================= */

        .top-header {

            height: 75px;

            background: white;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 30px;

            border-bottom:
                1px solid #e5e5e5;
        }


        .top-header h1 {

            font-size: 23px;

            color: #003b73;
        }


        .top-header p {

            color: #777;

            font-size: 13px;

            margin-top: 4px;
        }


        /* =========================
           ADMIN
        ========================= */

        .admin-profile {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .admin-avatar {

            width: 42px;
            height: 42px;

            background: #006edc;

            color: white;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .admin-info strong {

            display: block;

            font-size: 14px;
        }


        .admin-info span {

            display: block;

            color: #777;

            font-size: 12px;
        }


        /* =========================
           CONTENT
        ========================= */

        .content {

            padding: 30px;
        }


        /* =========================
           PAGE TOP
        ========================= */

        .page-top {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;
        }


        .page-title h2 {

            color: #003b73;

            font-size: 25px;

            margin-bottom: 6px;
        }


        .page-title p {

            color: #777;

            font-size: 14px;
        }


        /* =========================
           ADD CLIENT BUTTON
        ========================= */

        .add-client-btn {

            background: #006edc;

            color: white;

            border: none;

            padding: 13px 20px;

            border-radius: 7px;

            cursor: pointer;

            font-size: 14px;

            font-weight: bold;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            gap: 8px;

            transition: 0.3s;
        }


        .add-client-btn:hover {

            background: #005fc5;
        }


        /* =========================
           SEARCH
        ========================= */

        .search-box {

            background: white;

            padding: 20px;

            border-radius: 10px;

            margin-bottom: 20px;

            box-shadow:
                0 3px 12px rgba(0,0,0,0.06);
        }


        .search-wrapper {

            position: relative;

            max-width: 500px;
        }


        .search-wrapper i {

            position: absolute;

            left: 15px;

            top: 50%;

            transform:
                translateY(-50%);

            color: #888;
        }


        .search-wrapper input {

            width: 100%;

            height: 45px;

            border:
                1px solid #ddd;

            border-radius: 7px;

            padding:
                0 15px 0 42px;

            font-size: 14px;

            outline: none;
        }


        .search-wrapper input:focus {

            border-color: #006edc;

            box-shadow:
                0 0 0 3px
                rgba(0,110,220,0.10);
        }


        /* =========================
           CLIENT TABLE
        ========================= */

        .clients-box {

            background: white;

            border-radius: 10px;

            padding: 20px;

            box-shadow:
                0 3px 12px rgba(0,0,0,0.06);

            overflow-x: auto;
        }


        .clients-table {

            width: 100%;

            border-collapse: collapse;
        }


        .clients-table th {

            background: #f5f8fc;

            color: #555;

            text-align: left;

            padding: 14px;

            font-size: 13px;
        }


        .clients-table td {

            padding: 14px;

            border-bottom:
                1px solid #eee;

            font-size: 13px;
        }


        .clients-table tr:hover {

            background: #fafcff;
        }


        /* =========================
           CLIENT AVATAR
        ========================= */

        .client-name {

            display: flex;

            align-items: center;

            gap: 10px;

            font-weight: bold;
        }


        .client-avatar {

            width: 38px;
            height: 38px;

            background: #e8f2ff;

            color: #006edc;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        /* =========================
           STATUS
        ========================= */

        .status {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: bold;
        }


        .active-status {

            background: #e8f8ef;

            color: #159447;
        }


        .no-debt {

            background: #e8f2ff;

            color: #006edc;
        }


        /* =========================
           ACTION BUTTONS
        ========================= */

        .action-buttons {

            display: flex;

            gap: 7px;
        }


        .action-btn {

            width: 34px;
            height: 34px;

            border: none;

            border-radius: 6px;

            cursor: pointer;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .view-btn {

            background: #e8f2ff;

            color: #006edc;
        }


        .edit-btn {

            background: #fff4df;

            color: #d78300;
        }


        .delete-btn {

            background: #ffe9e9;

            color: #d33;
        }


        /* =========================
           EMPTY STATE
        ========================= */

        .empty-state {

            text-align: center;

            padding: 60px 20px;

            color: #888;
        }


        .empty-state i {

            font-size: 50px;

            color: #c5c5c5;

            margin-bottom: 15px;
        }


        .empty-state h3 {

            color: #555;

            margin-bottom: 7px;
        }


        .empty-state p {

            font-size: 13px;

            margin-bottom: 20px;
        }


        /* =========================
           MODAL
        ========================= */

        .modal {

            display: none;

            position: fixed;

            z-index: 100;

            left: 0;
            top: 0;

            width: 100%;
            height: 100%;

            background:
                rgba(0,0,0,0.45);

            align-items: center;

            justify-content: center;

            padding: 20px;
        }


        .modal-content {

            background: white;

            width: 100%;

            max-width: 500px;

            border-radius: 12px;

            padding: 25px;

            box-shadow:
                0 15px 40px
                rgba(0,0,0,0.25);
        }


        .modal-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;
        }


        .modal-header h2 {

            color: #003b73;

            font-size: 20px;
        }


        .close-btn {

            border: none;

            background: transparent;

            font-size: 22px;

            cursor: pointer;

            color: #777;
        }


        .form-group {

            margin-bottom: 17px;
        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            font-weight: bold;

            font-size: 13px;

            color: #333;
        }


        .form-group input,
        .form-group textarea {

            width: 100%;

            padding: 12px;

            border:
                1px solid #ddd;

            border-radius: 7px;

            outline: none;

            font-size: 14px;
        }


        .form-group input:focus,
        .form-group textarea:focus {

            border-color: #006edc;

            box-shadow:
                0 0 0 3px
                rgba(0,110,220,0.10);
        }


        .form-group textarea {

            resize: vertical;

            min-height: 80px;
        }


        .save-btn {

            width: 100%;

            height: 45px;

            border: none;

            border-radius: 7px;

            background: #006edc;

            color: white;

            font-size: 15px;

            font-weight: bold;

            cursor: pointer;
        }


        .save-btn:hover {

            background: #005fc5;
        }


        /* =========================
           FOOTER
        ========================= */

        .footer {

            text-align: center;

            color: #888;

            font-size: 12px;

            padding: 25px;
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 800px) {

            .sidebar {

                width: 220px;
            }

            .main-content {

                margin-left: 220px;

                width:
                    calc(100% - 220px);
            }

            .content {

                padding: 20px;
            }

        }


        @media (max-width: 650px) {

            .dashboard-container {

                display: block;
            }

            .sidebar {

                position: relative;

                width: 100%;
            }

            .main-content {

                margin-left: 0;

                width: 100%;
            }

            .page-top {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;
            }

            .admin-info {

                display: none;
            }

        }

    </style>

</head>


<body>


<div class="dashboard-container">


    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside class="sidebar">


        <div class="sidebar-logo">

            <div class="logo-icon">

                <i class="fa-solid fa-hand-holding-dollar"></i>

            </div>

            <h2>Loan Manager</h2>

            <p>Admin Panel</p>

        </div>


        <nav class="sidebar-menu">

            <div class="menu-title">
                Main Menu
            </div>


            <a href="dashboard.php">

                <i class="fa-solid fa-gauge"></i>

                <span>Dashboard</span>

            </a>


            <a href="customers.php" class="active">

                <i class="fa-solid fa-users"></i>

                <span>Clients</span>

            </a>


            <a href="sale1.html">

                <i class="fa-solid fa-file-circle-plus"></i>

                <span>New Loan / Record</span>

            </a>


            <a href="#">

                <i class="fa-solid fa-money-bill-wave"></i>

                <span>Payments</span>

            </a>


            <div class="menu-title">
                Management
            </div>


            <a href="#">

                <i class="fa-solid fa-chart-column"></i>

                <span>Reports</span>

            </a>


            <a href="#">

                <i class="fa-solid fa-print"></i>

                <span>Statements</span>

            </a>


            <a href="#">

                <i class="fa-solid fa-box"></i>

                <span>Products</span>

            </a>


            <a href="#">

                <i class="fa-solid fa-gear"></i>

                <span>Settings</span>

            </a>

        </nav>


        <div class="logout-section">

            <a href="logout.php">

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>Logout</span>

            </a>

        </div>

    </aside>



    <!-- =========================
         MAIN CONTENT
    ========================== -->

    <main class="main-content">


        <!-- HEADER -->

        <header class="top-header">

            <div>

                <h1>Clients</h1>

                <p>Manage your clients and their records</p>

            </div>


            <div class="admin-profile">

                <div class="admin-avatar">

                    <i class="fa-solid fa-user"></i>

                </div>

                <div class="admin-info">

                    <strong>
                        <?php
                        echo htmlspecialchars($admin_username);
                        ?>
                    </strong>

                    <span>Administrator</span>

                </div>

            </div>

        </header>



        <!-- CONTENT -->

        <section class="content">


            <!-- PAGE TITLE -->

            <div class="page-top">

                <div class="page-title">

                    <h2>Client Management</h2>

                    <p>
                        Add and manage people who owe you money.
                    </p>

                </div>


                <button
                    class="add-client-btn"
                    onclick="openModal()"
                >

                    <i class="fa-solid fa-user-plus"></i>

                    Add Client

                </button>

            </div>



            <!-- SEARCH -->

            <div class="search-box">

                <div class="search-wrapper">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Search client by name or phone..."
                        onkeyup="searchClients()"
                    >

                </div>

            </div>



            <!-- CLIENTS -->

            <div class="clients-box">

                <table
                    class="clients-table"
                    id="clientsTable"
                >

                    <thead>

                        <tr>

                            <th>Client</th>

                            <th>Phone</th>

                            <th>Address</th>

                            <th>Total Owed</th>

                            <th>Paid</th>

                            <th>Balance</th>

                            <th>Status</th>

                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody id="clientTableBody">

                        <!-- Temporary example -->

                        <tr>

                            <td>

                                <div class="client-name">

                                    <div class="client-avatar">

                                        <i class="fa-solid fa-user"></i>

                                    </div>

                                    Kalisa

                                </div>

                            </td>

                            <td>
                                0780000000
                            </td>

                            <td>
                                Kigali
                            </td>

                            <td>
                                1,200 RWF
                            </td>

                            <td>
                                500 RWF
                            </td>

                            <td>
                                700 RWF
                            </td>

                            <td>

                                <span class="status active-status">
                                    Owes
                                </span>

                            </td>

                            <td>

                                <div class="action-buttons">

                                    <button
                                        class="action-btn view-btn"
                                        title="View"
                                    >

                                        <i class="fa-solid fa-eye"></i>

                                    </button>


                                    <button
                                        class="action-btn edit-btn"
                                        title="Edit"
                                    >

                                        <i class="fa-solid fa-pen"></i>

                                    </button>


                                    <button
                                        class="action-btn delete-btn"
                                        title="Delete"
                                    >

                                        <i class="fa-solid fa-trash"></i>

                                    </button>

                                </div>

                            </td>

                        </tr>


                    </tbody>

                </table>

            </div>



            <!-- FOOTER -->

            <div class="footer">

                Loan Management System ©
                <?php echo date("Y"); ?>

            </div>

        </section>

    </main>

</div>



<!-- =========================
     ADD CLIENT MODAL
========================== -->

<div
    class="modal"
    id="clientModal"
>


    <div class="modal-content">


        <div class="modal-header">

            <h2>Add New Client</h2>

            <button
                class="close-btn"
                onclick="closeModal()"
            >
                &times;
            </button>

        </div>


        <form
            id="clientForm"
            onsubmit="addClient(event)"
        >


            <div class="form-group">

                <label>
                    Client Name *
                </label>

                <input
                    type="text"
                    id="clientName"
                    placeholder="Enter client name"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Phone Number
                </label>

                <input
                    type="tel"
                    id="clientPhone"
                    placeholder="Enter phone number"
                >

            </div>


            <div class="form-group">

                <label>
                    Address
                </label>

                <input
                    type="text"
                    id="clientAddress"
                    placeholder="Enter address"
                >

            </div>


            <div class="form-group">

                <label>
                    Notes
                </label>

                <textarea
                    id="clientNotes"
                    placeholder="Optional notes about this client"
                ></textarea>

            </div>


            <button
                type="submit"
                class="save-btn"
            >

                <i class="fa-solid fa-floppy-disk"></i>

                Save Client

            </button>


        </form>

    </div>

</div>



<script>


/* =========================
   OPEN MODAL
========================= */

function openModal() {

    document.getElementById("clientModal")
        .style.display = "flex";

}


/* =========================
   CLOSE MODAL
========================= */

function closeModal() {

    document.getElementById("clientModal")
        .style.display = "none";

}


/* =========================
   ADD CLIENT
========================= */

function addClient(event) {

    event.preventDefault();


    const name =
        document.getElementById("clientName").value.trim();

    const phone =
        document.getElementById("clientPhone").value.trim();

    const address =
        document.getElementById("clientAddress").value.trim();

    if (name === "") {

        alert("Please enter the client name.");

        return;
    }


    const tableBody =
        document.getElementById("clientTableBody");


    const row =
        document.createElement("tr");


    row.innerHTML = `

        <td>

            <div class="client-name">

                <div class="client-avatar">

                    <i class="fa-solid fa-user"></i>

                </div>

                ${escapeHTML(name)}

            </div>

        </td>

        <td>
            ${escapeHTML(phone || "-")}
        </td>

        <td>
            ${escapeHTML(address || "-")}
        </td>

        <td>
            0 RWF
        </td>

        <td>
            0 RWF
        </td>

        <td>
            0 RWF
        </td>

        <td>

            <span class="status no-debt">
                No Debt
            </span>

        </td>

        <td>

            <div class="action-buttons">

                <button
                    class="action-btn view-btn"
                    title="View"
                >
                    <i class="fa-solid fa-eye"></i>
                </button>

                <button
                    class="action-btn edit-btn"
                    title="Edit"
                >
                    <i class="fa-solid fa-pen"></i>
                </button>

                <button
                    class="action-btn delete-btn"
                    title="Delete"
                    onclick="deleteClient(this)"
                >
                    <i class="fa-solid fa-trash"></i>
                </button>

            </div>

        </td>

    `;


    tableBody.appendChild(row);


    document.getElementById("clientForm")
        .reset();


    closeModal();


    alert("Client added successfully.");

}


/* =========================
   DELETE CLIENT
========================= */

function deleteClient(button) {

    const confirmed =
        confirm(
            "Are you sure you want to remove this client?"
        );


    if (confirmed) {

        button.closest("tr").remove();

    }

}


/* =========================
   SEARCH CLIENTS
========================= */

function searchClients() {

    const search =
        document
            .getElementById("searchInput")
            .value
            .toLowerCase();


    const rows =
        document.querySelectorAll(
            "#clientTableBody tr"
        );


    rows.forEach(function(row) {

        const text =
            row.textContent.toLowerCase();


        if (text.includes(search)) {

            row.style.display = "";

        } else {

            row.style.display = "none";

        }

    });

}


/* =========================
   SAFE HTML
========================= */

function escapeHTML(value) {

    const div =
        document.createElement("div");

    div.textContent = value;

    return div.innerHTML;

}


/* =========================
   CLOSE MODAL WHEN CLICKING
   OUTSIDE
========================= */

window.onclick = function(event) {

    const modal =
        document.getElementById("clientModal");


    if (event.target === modal) {

        closeModal();

    }

};

</script>


</body>

</html>