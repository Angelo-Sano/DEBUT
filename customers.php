<?php

session_start();

/*
|--------------------------------------------------------------------------
| Protect page
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {
    header("Location: login.html");
    exit();
}


/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/

require_once "db.php";


/*
|--------------------------------------------------------------------------
| Delete client
|--------------------------------------------------------------------------
*/

if (
    isset($_GET["delete"]) &&
    filter_var($_GET["delete"], FILTER_VALIDATE_INT)
) {

    $id = (int) $_GET["delete"];

    $stmt = $pdo->prepare(
        "DELETE FROM clients WHERE id = ?"
    );

    $stmt->execute([$id]);

    header("Location: customers.php?deleted=1");
    exit();
}


/*
|--------------------------------------------------------------------------
| Add client
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | ADD
    |--------------------------------------------------------------------------
    */

    if ($action === "add") {

        $name = trim($_POST["name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $address = trim($_POST["address"] ?? "");


        if ($name === "") {

            header(
                "Location: customers.php?error=name"
            );

            exit();
        }


        $stmt = $pdo->prepare(
            "INSERT INTO clients
            (name, phone, address)
            VALUES (?, ?, ?)"
        );

        $stmt->execute([
            $name,
            $phone,
            $address
        ]);


        header(
            "Location: customers.php?added=1"
        );

        exit();
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    if ($action === "edit") {

        $id = filter_var(
            $_POST["id"] ?? "",
            FILTER_VALIDATE_INT
        );

        $name = trim($_POST["name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $address = trim($_POST["address"] ?? "");


        if (!$id || $name === "") {

            header(
                "Location: customers.php?error=edit"
            );

            exit();
        }


        $stmt = $pdo->prepare(
            "UPDATE clients
             SET name = ?, phone = ?, address = ?
             WHERE id = ?"
        );

        $stmt->execute([
            $name,
            $phone,
            $address,
            $id
        ]);


        header(
            "Location: customers.php?updated=1"
        );

        exit();
    }
}


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");


if ($search !== "") {

    $stmt = $pdo->prepare(
        "SELECT *
         FROM clients
         WHERE name LIKE ?
         OR phone LIKE ?
         OR address LIKE ?
         ORDER BY id DESC"
    );

    $like = "%" . $search . "%";

    $stmt->execute([
        $like,
        $like,
        $like
    ]);

} else {

    $stmt = $pdo->query(
        "SELECT *
         FROM clients
         ORDER BY id DESC"
    );
}


$clients = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| View one client
|--------------------------------------------------------------------------
*/

$view_client = null;

if (
    isset($_GET["view"]) &&
    filter_var($_GET["view"], FILTER_VALIDATE_INT)
) {

    $view_id = (int) $_GET["view"];

    $stmt = $pdo->prepare(
        "SELECT *
         FROM clients
         WHERE id = ?"
    );

    $stmt->execute([$view_id]);

    $view_client = $stmt->fetch();
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Clients - Loan Manager</title>


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css"
    >


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }


        body {
            background: #f4f6f9;
            color: #222;
        }


        /*
        ----------------------------------------
        SIDEBAR
        ----------------------------------------
        */

        .sidebar {

            position: fixed;

            left: 0;
            top: 0;

            width: 240px;
            height: 100vh;

            background: #172554;

            color: white;

            padding: 25px 15px;
        }


        .logo {

            text-align: center;

            margin-bottom: 35px;
        }


        .logo i {

            font-size: 35px;

            margin-bottom: 10px;
        }


        .logo h2 {

            font-size: 21px;
        }


        .menu a {

            display: flex;

            align-items: center;

            gap: 15px;

            color: white;

            text-decoration: none;

            padding: 14px 15px;

            margin-bottom: 8px;

            border-radius: 8px;

            transition: 0.2s;
        }


        .menu a:hover {

            background: #2563eb;
        }


        .menu a.active {

            background: #2563eb;
        }


        .menu i {

            width: 22px;
        }


        .logout {

            position: absolute;

            left: 15px;
            right: 15px;

            bottom: 25px;
        }


        .logout a {

            display: flex;

            align-items: center;

            gap: 15px;

            color: white;

            text-decoration: none;

            padding: 14px;

            background: #dc2626;

            border-radius: 8px;
        }


        /*
        ----------------------------------------
        MAIN
        ----------------------------------------
        */

        .main {

            margin-left: 240px;

            padding: 30px;
        }


        .topbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;
        }


        .topbar h1 {

            font-size: 30px;

            margin-bottom: 5px;
        }


        .topbar p {

            color: #666;
        }


        .add-button {

            border: none;

            background: #2563eb;

            color: white;

            padding: 12px 18px;

            border-radius: 7px;

            cursor: pointer;

            font-size: 15px;
        }


        .add-button:hover {

            background: #1d4ed8;
        }


        /*
        ----------------------------------------
        MESSAGES
        ----------------------------------------
        */

        .message {

            padding: 13px 17px;

            margin-bottom: 20px;

            border-radius: 7px;

            background: #dcfce7;

            color: #166534;
        }


        .error {

            background: #fee2e2;

            color: #991b1b;
        }


        /*
        ----------------------------------------
        SEARCH
        ----------------------------------------
        */

        .search-box {

            background: white;

            padding: 20px;

            border-radius: 10px;

            margin-bottom: 20px;

            box-shadow:
                0 2px 10px rgba(0,0,0,.08);
        }


        .search-form {

            display: flex;

            gap: 10px;
        }


        .search-form input {

            flex: 1;

            padding: 12px;

            border: 1px solid #ddd;

            border-radius: 7px;

            font-size: 15px;
        }


        .search-form button {

            border: none;

            background: #172554;

            color: white;

            padding: 12px 20px;

            border-radius: 7px;

            cursor: pointer;
        }


        /*
        ----------------------------------------
        TABLE
        ----------------------------------------
        */

        .table-box {

            background: white;

            padding: 20px;

            border-radius: 10px;

            box-shadow:
                0 2px 10px rgba(0,0,0,.08);

            overflow-x: auto;
        }


        table {

            width: 100%;

            border-collapse: collapse;
        }


        th,
        td {

            padding: 14px;

            text-align: left;

            border-bottom: 1px solid #eee;
        }


        th {

            background: #f8fafc;

            color: #555;
        }


        tr:hover {

            background: #f8fafc;
        }


        /*
        ----------------------------------------
        BUTTONS
        ----------------------------------------
        */

        .action {

            display: inline-block;

            padding: 7px 10px;

            border-radius: 5px;

            text-decoration: none;

            color: white;

            margin-right: 4px;

            font-size: 13px;
        }


        .view {

            background: #2563eb;
        }


        .edit {

            background: #f59e0b;
        }


        .delete {

            background: #dc2626;
        }


        .view:hover {

            background: #1d4ed8;
        }


        .edit:hover {

            background: #d97706;
        }


        .delete:hover {

            background: #b91c1c;
        }


        /*
        ----------------------------------------
        MODAL
        ----------------------------------------
        */

        .modal {

            display: none;

            position: fixed;

            z-index: 100;

            left: 0;
            top: 0;

            width: 100%;
            height: 100%;

            background: rgba(0,0,0,.5);

            justify-content: center;

            align-items: center;

            padding: 20px;
        }


        .modal-box {

            background: white;

            width: 100%;

            max-width: 500px;

            padding: 25px;

            border-radius: 10px;
        }


        .modal-header {

            display: flex;

            justify-content: space-between;

            margin-bottom: 20px;
        }


        .close {

            border: none;

            background: none;

            font-size: 25px;

            cursor: pointer;

            color: #555;
        }


        .form-group {

            margin-bottom: 15px;
        }


        .form-group label {

            display: block;

            margin-bottom: 6px;

            font-weight: bold;
        }


        .form-group input {

            width: 100%;

            padding: 12px;

            border: 1px solid #ddd;

            border-radius: 6px;

            font-size: 15px;
        }


        .save-button {

            width: 100%;

            border: none;

            background: #2563eb;

            color: white;

            padding: 13px;

            border-radius: 7px;

            cursor: pointer;

            font-size: 15px;
        }


        /*
        ----------------------------------------
        VIEW CLIENT
        ----------------------------------------
        */

        .client-details {

            background: white;

            padding: 25px;

            margin-bottom: 20px;

            border-radius: 10px;

            box-shadow:
                0 2px 10px rgba(0,0,0,.08);
        }


        .client-details h2 {

            margin-bottom: 20px;
        }


        .detail {

            padding: 10px 0;

            border-bottom: 1px solid #eee;
        }


        .detail strong {

            display: inline-block;

            width: 130px;
        }


        @media(max-width: 800px) {

            .sidebar {

                width: 70px;
            }


            .logo h2,
            .menu span,
            .logout span {

                display: none;
            }


            .main {

                margin-left: 70px;

                padding: 20px;
            }


            .topbar {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;
            }

        }

    </style>

</head>


<body>


<!--
==================================================
SIDEBAR
==================================================
-->

<div class="sidebar">


    <div class="logo">

        <i class="fa-solid fa-hand-holding-dollar"></i>

        <h2>Loan Manager</h2>

    </div>


    <div class="menu">


        


        <a
            href="customers.php"
            class="active"
        >

            <i class="fa-solid fa-users"></i>

            <span>Clients</span>

        </a>


        


    </div>


   

</div>


<!--
==================================================
MAIN
==================================================
-->

<div class="main">


    <div class="topbar">


        <div>

            <h1>Clients</h1>

            <p>Manage your clients</p>

        </div>


        <button
            class="add-button"
            onclick="openAddModal()"
        >

            <i class="fa-solid fa-plus"></i>

            Add Client

        </button>


    </div>


    <?php if (isset($_GET["added"])): ?>

        <div class="message">

            Client added successfully.

        </div>

    <?php endif; ?>


    <?php if (isset($_GET["updated"])): ?>

        <div class="message">

            Client updated successfully.

        </div>

    <?php endif; ?>


    <?php if (isset($_GET["deleted"])): ?>

        <div class="message">

            Client deleted successfully.

        </div>

    <?php endif; ?>


    <?php if (isset($_GET["error"])): ?>

        <div class="message error">

            Please enter a valid client name.

        </div>

    <?php endif; ?>


    <!--
    ==============================================
    VIEW CLIENT
    ==============================================
    -->

    <?php if ($view_client): ?>

        <div class="client-details">

            <div class="modal-header">

                <h2>Client Details</h2>

                <a
                    href="customers.php"
                    class="close"
                    style="text-decoration:none;"
                >
                    ×
                </a>

            </div>


            <div class="detail">

                <strong>Name:</strong>

                <?php
                echo htmlspecialchars(
                    $view_client["name"]
                );
                ?>

            </div>


            <div class="detail">

                <strong>Phone:</strong>

                <?php
                echo htmlspecialchars(
                    $view_client["phone"] ?? ""
                );
                ?>

            </div>


            <div class="detail">

                <strong>Address:</strong>

                <?php
                echo htmlspecialchars(
                    $view_client["address"] ?? ""
                );
                ?>

            </div>


            <div class="detail">

                <strong>Created:</strong>

                <?php
                echo htmlspecialchars(
                    $view_client["created_at"]
                );
                ?>

            </div>

        </div>

    <?php endif; ?>


    <!--
    ==============================================
    SEARCH
    ==============================================
    -->

    <div class="search-box">

        <form
            method="GET"
            action="customers.php"
            class="search-form"
        >

            <input
                type="text"
                name="search"
                placeholder="Search by name, phone or address..."
                value="<?php
                    echo htmlspecialchars($search);
                ?>"
            >

            <button type="submit">

                <i class="fa-solid fa-search"></i>

                Search

            </button>


            <?php if ($search !== ""): ?>

                <a
                    href="customers.php"
                    style="
                        padding:12px 15px;
                        background:#64748b;
                        color:white;
                        text-decoration:none;
                        border-radius:7px;
                    "
                >

                    Clear

                </a>

            <?php endif; ?>

        </form>

    </div>


    <!--
    ==============================================
    CLIENT TABLE
    ==============================================
    -->

    <div class="table-box">

        <table>

            <thead>

                <tr>

                    <th>#</th>

                    <th>Name</th>

                    <th>Phone</th>

                    <th>Address</th>

                    <th>Created</th>

                    <th>Actions</th>

                </tr>

            </thead>


            <tbody>


            <?php if (count($clients) === 0): ?>

                <tr>

                    <td
                        colspan="6"
                        style="text-align:center;padding:30px;"
                    >

                        No clients found.

                    </td>

                </tr>

            <?php endif; ?>


            <?php foreach ($clients as $client): ?>


                <tr>


                    <td>

                        <?php
                        echo (int) $client["id"];
                        ?>

                    </td>


                    <td>

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $client["name"]
                            );
                            ?>

                        </strong>

                    </td>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $client["phone"] ?? ""
                        );
                        ?>

                    </td>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $client["address"] ?? ""
                        );
                        ?>

                    </td>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $client["created_at"]
                        );
                        ?>

                    </td>


                    <td>


                        <!-- VIEW -->

                        <a
                            href="customers.php?view=<?php
                                echo (int) $client["id"];
                            ?>"
                            class="action view"
                        >

                            <i class="fa-solid fa-eye"></i>

                            View

                        </a>


                        <!-- EDIT -->

                        <a
                            href="#"
                            class="action edit"
                            onclick="openEditModal(
                                <?php echo (int) $client['id']; ?>,
                                '<?php echo htmlspecialchars(
                                    addslashes($client['name']),
                                    ENT_QUOTES
                                ); ?>',
                                '<?php echo htmlspecialchars(
                                    addslashes($client['phone'] ?? ''),
                                    ENT_QUOTES
                                ); ?>',
                                '<?php echo htmlspecialchars(
                                    addslashes($client['address'] ?? ''),
                                    ENT_QUOTES
                                ); ?>'
                            ); return false;"
                        >

                            <i class="fa-solid fa-pen"></i>

                            Edit

                        </a>


                        <!-- DELETE -->

                        <a
                            href="customers.php?delete=<?php
                                echo (int) $client["id"];
                            ?>"
                            class="action delete"
                            onclick="return confirm(
                                'Are you sure you want to delete this client?'
                            );"
                        >

                            <i class="fa-solid fa-trash"></i>

                            Delete

                        </a>


                    </td>


                </tr>


            <?php endforeach; ?>


            </tbody>

        </table>

    </div>


</div>


<!--
==================================================
ADD MODAL
==================================================
-->

<div
    id="addModal"
    class="modal"
>


    <div class="modal-box">


        <div class="modal-header">

            <h2>Add Client</h2>

            <button
                class="close"
                onclick="closeAddModal()"
            >
                ×
            </button>

        </div>


        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="add"
            >


            <div class="form-group">

                <label>Client Name *</label>

                <input
                    type="text"
                    name="name"
                    required
                    placeholder="Enter client name"
                >

            </div>


            <div class="form-group">

                <label>Phone</label>

                <input
                    type="text"
                    name="phone"
                    placeholder="Enter phone number"
                >

            </div>


            <div class="form-group">

                <label>Address</label>

                <input
                    type="text"
                    name="address"
                    placeholder="Enter address"
                >

            </div>


            <button
                type="submit"
                class="save-button"
            >

                <i class="fa-solid fa-save"></i>

                Save Client

            </button>

        </form>

    </div>

</div>


<!--
==================================================
EDIT MODAL
==================================================
-->

<div
    id="editModal"
    class="modal"
>


    <div class="modal-box">


        <div class="modal-header">

            <h2>Edit Client</h2>

            <button
                class="close"
                onclick="closeEditModal()"
            >
                ×
            </button>

        </div>


        <form method="POST">

            <input
                type="hidden"
                name="action"
                value="edit"
            >


            <input
                type="hidden"
                name="id"
                id="editId"
            >


            <div class="form-group">

                <label>Client Name *</label>

                <input
                    type="text"
                    name="name"
                    id="editName"
                    required
                >

            </div>


            <div class="form-group">

                <label>Phone</label>

                <input
                    type="text"
                    name="phone"
                    id="editPhone"
                >

            </div>


            <div class="form-group">

                <label>Address</label>

                <input
                    type="text"
                    name="address"
                    id="editAddress"
                >

            </div>


            <button
                type="submit"
                class="save-button"
            >

                <i class="fa-solid fa-save"></i>

                Update Client

            </button>

        </form>

    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| ADD MODAL
|--------------------------------------------------------------------------
*/

function openAddModal() {

    document.getElementById(
        "addModal"
    ).style.display = "flex";

}


function closeAddModal() {

    document.getElementById(
        "addModal"
    ).style.display = "none";

}


/*
|--------------------------------------------------------------------------
| EDIT MODAL
|--------------------------------------------------------------------------
*/

function openEditModal(
    id,
    name,
    phone,
    address
) {

    document.getElementById(
        "editId"
    ).value = id;


    document.getElementById(
        "editName"
    ).value = name;


    document.getElementById(
        "editPhone"
    ).value = phone;


    document.getElementById(
        "editAddress"
    ).value = address;


    document.getElementById(
        "editModal"
    ).style.display = "flex";

}


function closeEditModal() {

    document.getElementById(
        "editModal"
    ).style.display = "none";

}


/*
|--------------------------------------------------------------------------
| CLOSE MODAL WHEN CLICKING OUTSIDE
|--------------------------------------------------------------------------
*/

window.onclick = function(event) {

    let addModal =
        document.getElementById("addModal");

    let editModal =
        document.getElementById("editModal");


    if (event.target === addModal) {

        addModal.style.display = "none";

    }


    if (event.target === editModal) {

        editModal.style.display = "none";

    }

};

</script>


</body>

</html>