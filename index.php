<?php
include 'functions.php';

$filter = $_GET['filter'] ?? 'daily';
$dateRange = getDateRange($filter);
$data = readExpenseData($dateRange);

// Get all unique expense names
$expenseNames = array_keys($data);
sort($expenseNames);
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Expense Tracker</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <h2>Expense Tracker</h2>

        <div class="controls">
            <label for="filter">View:</label>
            <select id="filter" onchange="changeFilter(this.value)">
                <option <?= $filter == 'daily' ? 'selected' : '' ?> value="daily">Daily</option>
                <option <?= $filter == 'weekly' ? 'selected' : '' ?> value="weekly">Weekly</option>
                <option <?= $filter == 'monthly' ? 'selected' : '' ?> value="monthly">Monthly</option>
                <option <?= $filter == 'yearly' ? 'selected' : '' ?> value="yearly">Yearly</option>
            </select>
            <button onclick="openModal()">Add Expense</button>
            <form method="POST" action="export_csv.php" style="display:inline;">
                <input type="hidden" name="filter" value="<?= $filter ?>">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($_GET['filter'] ?? 'daily') ?>">
                <button type="submit">Export CSV</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Expense Name</th>
                    <?php foreach ($dateRange as $date): ?>
                        <th>
                            <button onclick="openDateDialog('<?= $date ?>')">
                                <?= $date ?>
                            </button>
                        </th>
                    <?php endforeach; ?>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expenseNames as $name): ?>
                    <tr>
                        <td><?= htmlspecialchars($name) ?></td>
                        <?php 
                            $rowTotal = 0;
                            foreach ($dateRange as $date): 
                                $amount = $data[$name][$date] ?? 0;
                                $rowTotal += $amount;
                        ?>
                        <td style="color: <?= $amount < 0 ? 'green' : 'red' ?>;">
                                <?= $amount != 0 ? 
                                    "$amount"
                                    : ''
                                ?>
                                
                            </td>
                        <?php endforeach; ?>
                        <td style="color: <?= $rowTotal < 0 ? 'green' : 'red' ?>;">
                            <strong><?= number_format($rowTotal) ?></strong>
                        </td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <?php 
                        $colTotals = [];
                        foreach ($dateRange as $date):
                            $colTotal = 0;
                            foreach ($data as $exp => $expData) {
                                $colTotal += $expData[$date] ?? 0;
                            }
                            $colTotals[] = $colTotal;
                    ?>
                        <th><strong><?= number_format($colTotal, 2) ?></strong></th>
                    <?php endforeach; ?>
                    <th><strong><?= number_format(array_sum($colTotals), 2) ?></strong></th>
                </tr>
            </tfoot>
        </table>

    <!-- Modal -->
        <div class="modal" id="expenseModal">
            <div class="modal-content">
                <span onclick="closeModal()" class="close">&times;</span>
                <h3>Add Expenses</h3>
                <form method="post" action="save_expense.php">
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($_GET['filter'] ?? 'daily') ?>">
                    <label>Date: <input type="date" name="date" required></label>
                    <div id="expensePairs">
                        <input type="text" name="expense_names[]" placeholder="Expense Name">
                        <input type="number" name="expense_values[]" placeholder="Amount">
                    </div>
                    <button type="button" onclick="addMoreExpense()">+ Add Another</button>
                    <br><br>
                    <button type="submit">Save</button>
                </form>

            </div>
        </div>

        <div id="dateModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span onclick="closeDateDialog()" class="close">&times;</span>
                <h3>Edit Expenses for <span id="selectedDateText"></span></h3>
                <form method="post" action="save_expense.php">
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($_GET['filter'] ?? 'daily') ?>">
                    <input type="hidden" name="date" id="selectedDateInput">
                    <div id="existingExpensesContainer">
                        <!-- dynamically filled with JS -->
                    </div>
                    <button type="button" onclick="addMoreExpense()">+ Add Expense</button>
                    <br><br>
                    <button type="submit">Save</button>
                </form>
            </div>
        </div>


        <script>
            function changeFilter(val) {
                window.location.href = "?filter=" + val;
            }

            function openModal() {
                document.getElementById("expenseModal").style.display = "block";
            }
            function closeModal() {
                document.getElementById("expenseModal").style.display = "none";
            }
            function addExpenseField() {
                const div = document.createElement("div");
                div.innerHTML = `<input type="text" name="expense_names[]" placeholder="Expense Name" required>
                                <input type="number" name="expense_values[]" placeholder="Amount" required>`;
                document.getElementById("expensePairs").appendChild(div);
            }

            document.getElementById("expenseForm").addEventListener("submit", function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch("save_expense.php", {
                    method: "POST",
                    body: formData
                }).then(res => res.text()).then(data => {
                    alert(data);
                    window.location.reload();
                });
            });

            function editEntry(name, date, amount) {
                openModal();
                document.querySelector('input[name="date"]').value = date;

                const container = document.getElementById("expensePairs");
                container.innerHTML = ''; // clear existing

                container.innerHTML = `<input type="text" name="expense_names[]" value="${name}" readonly>
                                    <input type="number" name="expense_values[]" value="${amount}" required>`;
            }

            function deleteEntry(name, date) {
                if (!confirm(`Delete expense "${name}" on ${date}?`)) return;

                fetch('delete_expense.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `name=${encodeURIComponent(name)}&date=${encodeURIComponent(date)}`
                })
                .then(res => res.text())
                .then(msg => {
                    alert(msg);
                    location.reload();
                });
            }

            
            function openDateDialog(date) {
                document.getElementById('selectedDateText').innerText = date;
                document.getElementById('selectedDateInput').value = date;

                const container = document.getElementById('existingExpensesContainer');
                container.innerHTML = '';

                // Inject existing expenses from PHP into JS
                const existingData = <?= json_encode($data); ?>;

                for (const name in existingData) {
                    const amount = existingData[name][date] || 0;

                    container.insertAdjacentHTML('beforeend', `
                    <input type="text" name="expense_names[]" value="${name}" placeholder="Expense Name">
                    <input type="number" name="expense_values[]" value="${amount}" placeholder="Amount">
                    <br>
                    `);
                }

                // Show modal
                document.getElementById('dateModal').style.display = 'block';
            }

            function addMoreExpense() {
                const container = document.getElementById('existingExpensesContainer');
                container.insertAdjacentHTML('beforeend', `
                    <input type="text" name="expense_names[]" placeholder="Expense Name">
                    <input type="number" name="expense_values[]" placeholder="Amount">
                    <br>
                `);
            }

            function closeDateDialog() {
            document.getElementById('dateModal').style.display = 'none';
            }

        </script>
    </body>
</html>
