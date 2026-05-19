(function() {
    var MAX_AGE = 99;

    function minAgeForGrade(grade) {
        var g = parseInt(grade, 10);
        if (isNaN(g) || g < 1 || g > 6) return null;
        return g + 4;
    }

    function computeAge(birthdate) {
        if (!birthdate) return null;
        var born = new Date(birthdate + 'T00:00:00');
        if (isNaN(born.getTime())) return null;
        var today = new Date();
        var age = today.getFullYear() - born.getFullYear();
        var m = today.getMonth() - born.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < born.getDate())) age--;
        if (age < 0 || born > today) return null;
        return age;
    }

    function validateStudentAge(grade, birthdate) {
        var min = minAgeForGrade(grade);
        if (min === null) return 'Choose a grade level from Grade 1 to Grade 6.';
        if (!birthdate || !birthdate.trim()) return 'Birthdate is required for students.';
        var age = computeAge(birthdate.trim());
        if (age === null) return 'Enter a valid birthdate that is not in the future.';
        var g = parseInt(grade, 10);
        if (age < min) {
            return 'For Grade ' + g + ', the student must be at least ' + min + ' years old (current age: ' + age + ').';
        }
        if (age > MAX_AGE) {
            return 'Student age must be ' + MAX_AGE + ' years or less (less than 100).';
        }
        return null;
    }

    function hintForGrade(grade) {
        var min = minAgeForGrade(grade);
        if (min === null) return 'Select a grade to see the required age range.';
        return 'Grade ' + parseInt(grade, 10) + ': age must be at least ' + min + ' and at most ' + MAX_AGE + ' years.';
    }

    var birthEl = document.getElementById('birthdate');
    var ageDisplay = document.getElementById('age_display');
    var ageHidden = document.getElementById('age');
    var gradeEl = document.getElementById('grade_level');
    var hintEl = document.getElementById('age-grade-hint');
    var form = document.querySelector('form');

    function syncAge() {
        if (!birthEl || !ageDisplay || !ageHidden) return;
        var raw = (birthEl.value || '').trim();
        if (raw === '') {
            ageDisplay.value = '';
            ageHidden.value = '';
            return;
        }
        var age = computeAge(raw);
        if (age === null) {
            ageDisplay.value = '';
            ageHidden.value = '';
            return;
        }
        ageDisplay.value = String(age);
        ageHidden.value = String(age);
    }

    function syncHint() {
        if (!hintEl || !gradeEl) return;
        var grade = gradeEl.value;
        var err = validateStudentAge(grade, birthEl ? birthEl.value : '');
        if (grade && birthEl && birthEl.value.trim() && err) {
            hintEl.style.color = '#c62828';
            hintEl.textContent = err;
        } else {
            hintEl.style.color = '#558b2f';
            hintEl.textContent = hintForGrade(grade);
        }
    }

    if (birthEl) {
        birthEl.setAttribute('max', new Date().toISOString().slice(0, 10));
        birthEl.addEventListener('change', function() { syncAge(); syncHint(); });
        birthEl.addEventListener('input', function() { syncAge(); syncHint(); });
        syncAge();
    }
    if (gradeEl) {
        gradeEl.addEventListener('change', syncHint);
    }
    syncHint();

    if (form) {
        form.addEventListener('submit', function(e) {
            var roleEl = document.getElementById('role');
            var studentFields = document.getElementById('student-fields');
            if (!studentFields || studentFields.style.display === 'none') return;
            var grade = gradeEl ? gradeEl.value : '';
            var birth = birthEl ? birthEl.value : '';
            var err = validateStudentAge(grade, birth);
            if (err) {
                e.preventDefault();
                alert(err);
                if (hintEl) {
                    hintEl.style.color = '#c62828';
                    hintEl.textContent = err;
                }
                (birthEl && !birth ? birthEl : gradeEl)?.focus();
            }
        });
    }
})();
