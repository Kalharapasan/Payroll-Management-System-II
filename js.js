(function(){
            const modalEl = document.getElementById('modalRow');
            const bsModal = new bootstrap.Modal(modalEl);
            const frm = document.getElementById('frmRow');
            const calcGross = document.getElementById('calc_gross');
            const calcDed = document.getElementById('calc_ded');
            const calcNet = document.getElementById('calc_net');

            function recalc() {
                const inner = parseFloat(document.getElementById('inner_city').value||0);
                const basic = parseFloat(document.getElementById('basic_salary').value||0);
                const overtime = parseFloat(document.getElementById('overtime').value||0);
                const gross = inner + basic + overtime;
                const taxable = gross * 0.09;
                const pension = gross * 0.055;
                const student = gross * 0.025;
                const ni = gross * 0.023;
                const ded = taxable + pension + student + ni;
                const net = gross - ded;
                calcGross.textContent = gross.toFixed(2);
                calcDed.textContent = ded.toFixed(2);
                calcNet.textContent = net.toFixed(2);
            }
            ['inner_city','basic_salary','overtime'].forEach(id=>{
                document.getElementById(id).addEventListener('input', recalc);
            });

            document.getElementById('btnNew').addEventListener('click', ()=>{
                frm.reset();
                document.getElementById('formAction').value = 'add';
                document.getElementById('formId').value = 0;
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Add Employee';
                document.getElementById('modalSave').innerHTML = '<i class="fas fa-save"></i> Add Employee';
                recalc();
                bsModal.show();
            });


            document.querySelectorAll('.btnEdit').forEach(btn=>{
                btn.addEventListener('click', (e)=>{
                    const row = JSON.parse(btn.getAttribute('data-row'));
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('formId').value = row.id;
                    for (const k in row) {
                        const el = document.getElementById(k);
                        if (el) el.value = row[k] ?? '';
                    }
                    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Edit Employee #' + row.id;
                    document.getElementById('modalSave').innerHTML = '<i class="fas fa-save"></i> Update Employee';
                    recalc();
                    bsModal.show();
                });
            });

            document.querySelectorAll('.btnView').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    const row = JSON.parse(btn.getAttribute('data-row'));
                    frm.reset();
                    for (const k in row) {
                        const el = document.getElementById(k);
                        if (el) el.value = row[k] ?? '';
                    }
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('formId').value = row.id;
                    Array.from(frm.elements).forEach(i=>i.disabled = true);
                    document.getElementById('modalSave').style.display = 'none';
                    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-eye"></i> View Employee #' + row.id;
                    recalc();
                    bsModal.show();
                    modalEl.addEventListener('hidden.bs.modal', function _reen(){
                        Array.from(frm.elements).forEach(i=>i.disabled = false);
                        document.getElementById('modalSave').style.display = '';
                        modalEl.removeEventListener('hidden.bs.modal', _reen);
                    });
                });
            });

        })();