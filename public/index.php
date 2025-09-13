<?php
require_once __DIR__ . '/../src/auth.php';
require_once '/var/www/config/db.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}


$user_id = $_SESSION['user_id'] ?? null;
$roles = $_SESSION['roles'] ?? [];
$fullname = '';
if ($user_id) {
    $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if ($row && !empty($row['name'])) {
        $fullname = $row['name'];
    }
}


/*
// Greeting by time
$hour = (int)date('G');
if ($hour >= 5 && $hour < 12) {
    $greeting = 'สวัสดีตอนเช้า';
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = 'สวัสดีตอนบ่าย';
} elseif ($hour >= 17 && $hour < 21) {
    $greeting = 'สวัสดีตอนเย็น';
} else {
    $greeting = 'สวัสดีตอนกลางคืน';
}
*/

// ดึงข้อมูลสรุป
$user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$form_count = $pdo->query("SELECT COUNT(*) FROM responses")->fetchColumn();

// 1. เพศ (Sex)
$sex_data = $pdo->query("SELECT answer, COUNT(*) as total FROM answers WHERE question_id='Q4' GROUP BY answer")->fetchAll(PDO::FETCH_KEY_PAIR);

// 2. อายุ (Age)
$age_data = $pdo->query("SELECT answer FROM answers WHERE question_id='Q5'")->fetchAll(PDO::FETCH_COLUMN);

// 3. สถานะ (Status)
$status_data = $pdo->query("SELECT answer, COUNT(*) as total FROM answers WHERE question_id='Q6' GROUP BY answer")->fetchAll(PDO::FETCH_KEY_PAIR);

// 4. การศึกษา (Education)
$edu_data = $pdo->query("SELECT answer, COUNT(*) as total FROM answers WHERE question_id='Q7' GROUP BY answer")->fetchAll(PDO::FETCH_KEY_PAIR);

// 5. อาชีพ (Occupation)
$job_data = $pdo->query("SELECT answer, COUNT(*) as total FROM answers WHERE question_id='Q8' GROUP BY answer")->fetchAll(PDO::FETCH_KEY_PAIR);

// 6. เงินเดือน (Income)
$income_data = $pdo->query("SELECT answer FROM answers WHERE question_id='Q9'")->fetchAll(PDO::FETCH_COLUMN);

// 7. โรคประจำตัว (Chronic Disease)
$disease_data = $pdo->query("SELECT answer FROM answers WHERE question_id='Q10'")->fetchAll(PDO::FETCH_COLUMN);

// 8. การออกกำลังกาย (Exercise)
$exercise_data = $pdo->query("SELECT answer, COUNT(*) as total FROM answers WHERE question_id='Q11' GROUP BY answer")->fetchAll(PDO::FETCH_KEY_PAIR);

// 9. การสูบบุหรี่ (Smoking)
$smoke_data = $pdo->query("SELECT answer, COUNT(*) as total FROM answers WHERE question_id='Q12' GROUP BY answer")->fetchAll(PDO::FETCH_KEY_PAIR);

// 10. การดื่มสุรา (Alcohol)
$alcohol_data = $pdo->query("SELECT answer, COUNT(*) as total FROM answers WHERE question_id='Q13' GROUP BY answer")->fetchAll(PDO::FETCH_KEY_PAIR);

// 11. ที่อยู่อาศัย (Residence)
$residence_data = $pdo->query("SELECT answer, COUNT(*) as total FROM answers WHERE question_id='Q14' GROUP BY answer")->fetchAll(PDO::FETCH_KEY_PAIR);

// 12. ลักษณะครอบครัว (Family Type)
$family_data = $pdo->query("SELECT answer, COUNT(*) as total FROM answers WHERE question_id='Q15' GROUP BY answer")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="container py-5">
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">จำนวนผู้ใช้ทั้งหมด</h5>
                        <p class="display-5"><?= $user_count ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">จำนวนฟอร์มที่ถูกกรอก</h5>
                        <p class="display-5"><?= $form_count ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-6 mb-4">
                <div class="card p-3">
                    <h6>เพศ</h6><canvas id="sexChart"></canvas>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card p-3">
                    <h6>อายุ</h6><canvas id="ageChart"></canvas>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card p-3">
                    <h6>สถานะ</h6><canvas id="statusChart"></canvas>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card p-3">
                    <h6>การศึกษา</h6><canvas id="eduChart"></canvas>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card p-3">
                    <h6>อาชีพ</h6><canvas id="jobChart"></canvas>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card p-3">
                    <h6>เงินเดือน</h6><canvas id="incomeChart"></canvas>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card p-3">
                    <h6>โรคประจำตัว</h6><canvas id="diseaseChart"></canvas>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card p-3">
                    <h6>การออกกำลังกาย</h6><canvas id="exerciseChart"></canvas>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card p-3">
                    <h6>การสูบบุหรี่</h6><canvas id="smokeChart"></canvas>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card p-3">
                    <h6>การดื่มสุรา</h6><canvas id="alcoholChart"></canvas>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card p-3">
                    <h6>ที่อยู่อาศัย</h6><canvas id="residenceChart"></canvas>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card p-3">
                    <h6>ลักษณะครอบครัว</h6><canvas id="familyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    </div>
    <script>
        // Helper: PHP array to JS
        function phpToJsArray(obj) {
            return obj ? Object.entries(obj) : [];
        }

        // 1. เพศ (Sex) - Bar Chart แนวนอน
        const sexData = <?php echo json_encode($sex_data); ?>;
        new Chart(document.getElementById('sexChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(sexData),
                datasets: [{
                    label: 'จำนวน',
                    data: Object.values(sexData),
                    backgroundColor: '#4e79a7',
                }]
            },
            options: {
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // 2. อายุ (Age) - Bar Chart (แบ่งช่วง)
        const ageRaw = <?php echo json_encode($age_data); ?>.map(Number).filter(x => !isNaN(x));
        const ageBins = Array(10).fill(0); // 0-9, 10-19, ..., 90-100
        ageRaw.forEach(a => {
            let idx = Math.min(Math.floor(a / 10), 9);
            ageBins[idx]++;
        });
        const ageLabels = Array.from({
            length: 10
        }, (_, i) => `${i*10}-${i*10+9}`);
        ageLabels[9] = '90-100';
        new Chart(document.getElementById('ageChart'), {
            type: 'bar',
            data: {
                labels: ageLabels,
                datasets: [{
                    label: 'จำนวน',
                    data: ageBins,
                    backgroundColor: '#f28e2b'
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // 3. สถานะ (Status) - Bar Chart
        const statusData = <?php echo json_encode($status_data); ?>;
        new Chart(document.getElementById('statusChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(statusData),
                datasets: [{
                    label: 'จำนวน',
                    data: Object.values(statusData),
                    backgroundColor: '#e15759'
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // 4. การศึกษา (Education) - Bar Chart แนวนอน
        const eduData = <?php echo json_encode($edu_data); ?>;
        new Chart(document.getElementById('eduChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(eduData),
                datasets: [{
                    label: 'จำนวน',
                    data: Object.values(eduData),
                    backgroundColor: '#76b7b2'
                }]
            },
            options: {
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // 5. อาชีพ (Occupation) - Bar Chart แนวนอน
        const jobData = <?php echo json_encode($job_data); ?>;
        new Chart(document.getElementById('jobChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(jobData),
                datasets: [{
                    label: 'จำนวน',
                    data: Object.values(jobData),
                    backgroundColor: '#59a14f'
                }]
            },
            options: {
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // 6. เงินเดือน (Income) - Bar Chart (แบ่งช่วง)
        const incomeRaw = <?php echo json_encode($income_data); ?>.map(Number).filter(x => !isNaN(x));
        const incomeBins = Array(10).fill(0); // 0-9999, ..., 90000-100000
        incomeRaw.forEach(i => {
            let idx = Math.min(Math.floor(i / 10000), 9);
            incomeBins[idx]++;
        });
        const incomeLabels = Array.from({
            length: 10
        }, (_, i) => `${i*10000}-${i*10000+9999}`);
        incomeLabels[9] = '90000-100000';
        new Chart(document.getElementById('incomeChart'), {
            type: 'bar',
            data: {
                labels: incomeLabels,
                datasets: [{
                    label: 'จำนวน',
                    data: incomeBins,
                    backgroundColor: '#edc949'
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // 7. โรคประจำตัว (Chronic Disease) - Bar Chart
        const diseaseRaw = <?php echo json_encode($disease_data); ?>;
        const diseaseCount = {};
        diseaseRaw.forEach(val => {
            if (val) val.split(',').forEach(d => {
                d = d.trim();
                if (d) diseaseCount[d] = (diseaseCount[d] || 0) + 1;
            });
        });
        new Chart(document.getElementById('diseaseChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(diseaseCount),
                datasets: [{
                    label: 'จำนวน',
                    data: Object.values(diseaseCount),
                    backgroundColor: '#af7aa1'
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // 8. การออกกำลังกาย (Exercise) - Donut Chart
        // 8. การออกกำลังกาย (Exercise) - Bar Chart
        const exerciseData = <?php echo json_encode($exercise_data); ?>;
        new Chart(document.getElementById('exerciseChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(exerciseData),
                datasets: [{
                    label: 'จำนวน',
                    data: Object.values(exerciseData),
                    backgroundColor: '#4e79a7',
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // 9. การสูบบุหรี่ (Smoking) - Pie Chart
        const smokeData = <?php echo json_encode($smoke_data); ?>;
        new Chart(document.getElementById('smokeChart'), {
            type: 'pie',
            data: {
                labels: Object.keys(smokeData),
                datasets: [{
                    data: Object.values(smokeData),
                    backgroundColor: ['#4e79a7', '#f28e2b', '#e15759']
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // 10. การดื่มสุรา (Alcohol) - Pie Chart
        const alcoholData = <?php echo json_encode($alcohol_data); ?>;
        new Chart(document.getElementById('alcoholChart'), {
            type: 'pie',
            data: {
                labels: Object.keys(alcoholData),
                datasets: [{
                    data: Object.values(alcoholData),
                    backgroundColor: ['#4e79a7', '#f28e2b', '#e15759']
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // 11. ที่อยู่อาศัย (Residence) - Donut Chart
        const residenceData = <?php echo json_encode($residence_data); ?>;
        new Chart(document.getElementById('residenceChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(residenceData),
                datasets: [{
                    data: Object.values(residenceData),
                    backgroundColor: ['#4e79a7', '#f28e2b', '#e15759', '#76b7b2', '#59a14f']
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // 12. ลักษณะครอบครัว (Family Type) - Donut Chart
        const familyData = <?php echo json_encode($family_data); ?>;
        new Chart(document.getElementById('familyChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(familyData),
                datasets: [{
                    data: Object.values(familyData),
                    backgroundColor: ['#4e79a7', '#f28e2b', '#e15759', '#76b7b2']
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
    <?php include 'footer.php'; ?>
</body>

</html>