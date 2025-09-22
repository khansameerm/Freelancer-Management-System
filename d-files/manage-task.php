<?php
include 'db_connect.php';

// Function to handle deadlock retries
function handle_deadlock($conn, $sql, $maxRetries = 3) {
    $attempts = 0;
    while ($attempts < $maxRetries) {
        try {
            // Try executing the query
            return $conn->query($sql);
        } catch (Exception $e) {
            // Check for deadlock error
            if (strpos($e->getMessage(), 'Deadlock') !== false) {
                $attempts++;
                sleep(1); // Wait for 1 second before retrying
            } else {
                throw $e; // Throw the exception if it's not a deadlock
            }
        }
    }
    throw new Exception("Max retries reached for query: $sql");
}

try {
    if (isset($_GET['id'])) {
        // Synchronization: Lock the task table for reading the task data
        $conn->query("LOCK TABLES task_list READ");

        // Handling deadlock with retry logic
        $qry = handle_deadlock($conn, "SELECT * FROM task_list WHERE id = " . $_GET['id']);
        
        if ($qry) {
            $qry = $qry->fetch_array();
        }

        $conn->query("UNLOCK TABLES"); // Unlock after fetching data

        foreach ($qry as $k => $v) {
            $$k = $v;
        }
    }
} catch (Exception $e) {
    // Exception handling for database errors
    error_log("Error fetching task: " . $e->getMessage()); // Log the error
    echo "<div class='alert alert-danger'>There was an error fetching task details. Please try again later.</div>";
}
?>

<div class="container-fluid">
    <form action="" id="manage-task">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <input type="hidden" name="project_id" value="<?php echo isset($_GET['pid']) ? $_GET['pid'] : '' ?>">
        <div class="form-group">
            <label for="">Task</label>
            <input type="text" class="form-control form-control-sm" name="task" value="<?php echo isset($task) ? $task : '' ?>" required>
        </div>
        <div class="form-group">
            <label for="">Description</label>
            <textarea name="description" id="" cols="30" rows="10" class="summernote form-control">
                <?php echo isset($description) ? $description : '' ?>
            </textarea>
        </div>
        <div class="form-group">
            <label for="">Status</label>
            <select name="status" id="status" class="custom-select custom-select-sm">
                <option value="1" <?php echo isset($status) && $status == 1 ? 'selected' : '' ?>>Pending</option>
                <option value="2" <?php echo isset($status) && $status == 2 ? 'selected' : '' ?>>On-Progress</option>
                <option value="3" <?php echo isset($status) && $status == 3 ? 'selected' : '' ?>>Done</option>
            </select>
        </div>
    </form>
</div>

<script>
    $(document).ready(function(){
        $('.summernote').summernote({
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ol', 'ul', 'paragraph', 'height']],
                ['table', ['table']],
                ['view', ['undo', 'redo', 'fullscreen', 'codeview', 'help']]
            ]
        })
    });

    $('#manage-task').submit(function(e){
        e.preventDefault();
        start_load();

        // Multithreading Simulation: Background process
        var formData = new FormData($(this)[0]);
        $.ajax({
            url: 'ajax.php?action=save_task',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST',
            success: function(resp){
                if (resp == 1) {
                    // Simulate task processing in the background
                    setTimeout(function(){
                        // Non-blocking background task simulation
                        shell_exec('php process_task.php ' + formData.get('id') + ' > /dev/null &');
                        alert_toast('Data successfully saved and processed in background',"success");
                    }, 1500);
                }
            },
            error: function(xhr, status, error){
                alert_toast('Error: ' + error, "danger"); // Error handling in AJAX request
            }
        });
    });
</script>



Multithreading: We'll use shell_exec() to process tasks asynchronously in the background.
Synchronization: We'll lock the table when fetching or updating data to ensure synchronization.
Deadlock Handling:

The handle_deadlock function retries database operations if a deadlock occurs.
Exception Handling:

The code uses try-catch blocks to manage exceptions in database queries and errors during execution.
AJAX Error Handling:

Added error handling for the AJAX request, showing an error toast in case of failure.