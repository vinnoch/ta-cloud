<fieldset class="ta-understanding">
    <legend>Pahami pilihan tugas akhir</legend>
    <div class="ta-understanding__concepts">
        <p><strong>Skripsi</strong> berfokus pada penelitian untuk menjelaskan, menguji, mengevaluasi, membandingkan, memprediksi, atau mengembangkan pengetahuan. Luaran utamanya berupa laporan ilmiah dan hasil penelitian. Artefak dapat menjadi bagian penelitian jika diperlukan.</p>
        <p><strong>Non-Skripsi</strong> berfokus pada karya atau proyek yang menjawab kebutuhan pengguna atau proses bisnis. Luaran dapat berupa sistem, aplikasi, prototipe, dasbor, model operasional, atau publikasi sesuai panduan. Artefak tetap harus dievaluasi.</p>
    </div>
    <label class="ta-understanding__check">
        <input type="checkbox" name="understands_tugas_akhir_types" value="1" @checked(old('understands_tugas_akhir_types')) @if($required ?? false) required @endif data-ta-understanding>
        <span>Saya memahami perbedaan Skripsi dan Non-Skripsi, serta memilih jenis tugas akhir yang sesuai.</span>
    </label>
    @error('understands_tugas_akhir_types')
        <small class="field-error">{{ $message }}</small>
    @enderror
</fieldset>

@once
    @push('scripts')
        <script>
            document.addEventListener('submit', function (event) {
                if (event.submitter?.value !== 'published') return;

                const checkbox = event.target.querySelector('[data-ta-understanding]');
                if (!checkbox) return;

                checkbox.setCustomValidity(checkbox.checked ? '' : 'Centang pernyataan pemahaman sebelum mengajukan proposal.');
                if (!checkbox.checked) {
                    event.preventDefault();
                    checkbox.reportValidity();
                }
            });

            document.addEventListener('change', function (event) {
                if (event.target.matches('[data-ta-understanding]')) event.target.setCustomValidity('');
            });
        </script>
    @endpush
@endonce
