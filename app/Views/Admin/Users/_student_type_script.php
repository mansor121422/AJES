        function syncStudentTypeFields() {
            var typeEl = document.getElementById('student_type');
            var wrap = document.getElementById('previous-school-wrap');
            var prevEl = document.getElementById('previous_school');
            if (!typeEl || !wrap) {
                return;
            }
            var needsPrevious = typeEl.value === 'new' || typeEl.value === 'transferee';
            wrap.style.display = needsPrevious ? 'block' : 'none';
            if (prevEl) {
                prevEl.required = needsPrevious;
                if (!needsPrevious) {
                    prevEl.value = '';
                }
            }
            if (typeEl) {
                typeEl.required = document.getElementById('student-fields') &&
                    document.getElementById('student-fields').style.display !== 'none';
            }
        }

        var studentTypeEl = document.getElementById('student_type');
        if (studentTypeEl) {
            studentTypeEl.addEventListener('change', syncStudentTypeFields);
            syncStudentTypeFields();
        }
