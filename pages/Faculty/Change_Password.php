<?php

session_start();

include "../connection.php";

if (!isset($_SESSION["user_id"])) {
    header("../location:Sign_In_Form.php");
    exit();
} else {
    if (isset($_POST["Update_Password_Btn"])) {
        if ($_POST["New_Password"] === $_POST["Confirm_Password"]) {
            $password = password_hash($_POST["New_Password"], PASSWORD_DEFAULT);

            $query_update_password = "UPDATE users SET firstname = ?, lastname = ?, password = ? WHERE user_id = ?";

            $stmt = $connection->prepare($query_update_password);
            $stmt->bind_param("sssi", $_POST["fname"], $_POST["lname"], $password, $_SESSION["user_id"]);

            if ($stmt->execute()) {
                header("location:My_Profile.php?message=Password Changed Successfully.");
                exit();
            }
        } else {
            header("location:My_Profile.php?error=New and Confirm Password are not same.");
            exit();
        }
    }

    if (isset($_POST["Update_Details_Btn"])) {
        $query_update_details = "UPDATE users SET firstname = ?, lastname = ? WHERE user_id = ?";

        $stmt = $connection->prepare($query_update_details);
        $stmt->bind_param("ssi", $_POST["fname"], $_POST["lname"], $_SESSION["user_id"]);

        if ($stmt->execute()) {
            header("location:My_Profile.php?message=Details Updated Successfully.");
            exit();
        }
    } else {
        header("../location:Sign_Up_Form.php");
        exit();
    }
}

?>