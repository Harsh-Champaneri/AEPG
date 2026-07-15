<?php
include "../connection.php";

$search = $_POST['searchText'];
$branch = $_POST['branchName'];

$query = $connection->prepare("
    SELECT * FROM subject 
    WHERE branch_name = ?
    AND (subject_name LIKE ? OR subject_code LIKE ?)
");

$searchTerm = "%" . $search . "%";
$query->bind_param("sss", $branch, $searchTerm, $searchTerm);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        $q = $connection->prepare("SELECT firstname, lastname FROM users WHERE email=?");
        $q->bind_param("s", $row['email']);
        $q->execute();
        $r = $q->get_result();
        $faculty = "";
        if ($f = $r->fetch_assoc()) {
            $faculty = $f['firstname'] . " " . $f['lastname'];
        }

        echo "
        <tr>
            <td>{$row['semester']}</td>
            <td>{$row['subject_code']}</td>
            <td>{$row['subject_name']}</td>
            <td>{$faculty}</td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='4'>No matching subjects found</td></tr>";
}

?>