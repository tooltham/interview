<?php
// Metadata สำหรับแบบสอบถาม ส่วนที่ 1 (ข้อมูลส่วนบุคคล)
return [
    [
        "id" => "Q1",
        "label" => "คำนำหน้า",
        "type" => "radio",
        "options" => ["นาย", "นาง", "นางสาว", "อื่นๆ"],
        "required" => true
    ],
    [
        "id" => "Q2",
        "label" => "ชื่อ - นามสกุล",
        "type" => "text",
        "required" => true
    ],
    [
        "id" => "Q3",
        "label" => "ที่อยู่",
        "type" => "textarea",
        "required" => true
    ],
    [
        "id" => "Q4",
        "label" => "เพศ",
        "type" => "radio",
        "options" => ["ชาย", "หญิง"],
        "required" => true
    ],
    [
        "id" => "Q5",
        "label" => "อายุ (ปี)",
        "type" => "number",
        "required" => true
    ],
    [
        "id" => "Q6",
        "label" => "สถานภาพสมรส",
        "type" => "radio",
        "options" => ["โสด", "สมรส", "หม้าย/หย่า/แยก"],
        "required" => true
    ],
    [
        "id" => "Q7",
        "label" => "ระดับการศึกษา",
        "type" => "radio",
        "options" => ["ไม่ได้เรียน", "ประถมศึกษา", "มัธยมศึกษา/ปวช.", "อนุปริญญา/ปวส.", "ปริญญาตรี", "สูงกว่าปริญญาตรี"],
        "required" => true
    ],
    [
        "id" => "Q8",
        "label" => "อาชีพ",
        "type" => "radio",
        "options" => ["ไม่ได้ประกอบอาชีพ", "เกษตรกร", "รับจ้าง", "ค้าขาย", "รับราชการ/รัฐวิสาหกิจ", "อื่นๆ"],
        "required" => true
    ],
    [
        "id" => "Q9",
        "label" => "รายได้เฉลี่ยต่อเดือน (บาท)",
        "type" => "number",
        "required" => false
    ],
    [
        "id" => "Q10",
        "label" => "โรคประจำตัว",
        "type" => "checkbox",
        "options" => ["ไม่มี", "ความดันโลหิตสูง", "เบาหวาน", "โรคหัวใจ", "อื่นๆ"],
        "required" => false
    ],
    [
        "id" => "Q11",
        "label" => "การออกกำลังกาย",
        "type" => "radio",
        "options" => ["ไม่เคย", "1-2 ครั้ง/สัปดาห์", "3-5 ครั้ง/สัปดาห์", "ทุกวัน"],
        "required" => false
    ],
    [
        "id" => "Q12",
        "label" => "การสูบบุหรี่",
        "type" => "radio",
        "options" => ["ไม่สูบ", "เคยสูบแต่เลิกแล้ว", "สูบ"],
        "required" => false
    ],
    [
        "id" => "Q13",
        "label" => "การดื่มสุรา",
        "type" => "radio",
        "options" => ["ไม่ดื่ม", "เคยดื่มแต่เลิกแล้ว", "ดื่ม"],
        "required" => false
    ],
    [
        "id" => "Q14",
        "label" => "ที่อยู่อาศัย",
        "type" => "radio",
        "options" => ["บ้านตนเอง", "บ้านบุตร", "บ้านญาติ/ผู้อื่น"],
        "required" => false
    ],
    [
        "id" => "Q15",
        "label" => "ลักษณะครอบครัว",
        "type" => "radio",
        "options" => ["อยู่คนเดียว", "อยู่กับคู่สมรส", "อยู่กับลูก/หลาน"],
        "required" => false
    ]
];
