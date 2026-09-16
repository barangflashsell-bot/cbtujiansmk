@extends('layouts.app')

@section('title', 'Tambah Soal - CBT Administrator')

@section('content')
<style>
    .tinymce-mock-container {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        overflow: hidden;
        background: #ffffff;
    }
    .tinymce-menubar {
        display: flex;
        gap: 12px;
        padding: 6px 12px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        font-size: 12.5px;
        color: #334155;
    }
    .tinymce-menubar span {
        cursor: pointer;
        padding: 2px 6px;
        border-radius: 4px;
    }
    .tinymce-menubar span:hover {
        background: #e2e8f0;
    }
    .tinymce-toolbar {
        display: flex;
        gap: 6px;
        padding: 6px 12px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        align-items: center;
        flex-wrap: wrap;
    }
    .tb-btn {
        background: none;
        border: 1px solid transparent;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 13px;
        cursor: pointer;
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .tb-btn:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }
    .tb-separator {
        width: 1px;
        height: 18px;
        background: #e2e8f0;
        margin: 0 4px;
    }
    .tinymce-statusbar {
        padding: 6px 12px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        font-size: 11px;
        color: #94a3b8;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
</style>

<div class="content-header">
    <div>
        <h1 class="page-title">Tambah Soal Ujian</h1>
        <p class="page-subtitle">Masukkan butir soal, pilihan jawaban, kunci jawaban atau bobot soal essai</p>
    </div>
    @php
        $backUrl = request('subject_id') ? route('admin.questions.index', ['subject_id' => request('subject_id')]) : route('admin.questions.index');
    @endphp
    <a href="{{ $backUrl }}" class="btn btn-secondary">&larr; Kembali ke Daftar Soal</a>
</div>

<form method="POST" action="{{ route('admin.questions.store') }}">
    @csrf

    <div class="card" style="margin-bottom: 20px;">
        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label class="form-label" for="subject_id">Mata Pelajaran / Bank Soal <span style="color:var(--color-rose-500)">*</span></label>
                <select name="subject_id" id="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Mata Pelajaran --</option>
                    @foreach($subjects as $sb)
                        <option value="{{ $sb->id }}" {{ (old('subject_id', request('subject_id')) == $sb->id) ? 'selected' : '' }}>
                            {{ $sb->name }} ({{ $sb->code }})
                        </option>
                    @endforeach
                </select>
                @error('subject_id')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="flex: 1;">
                <label class="form-label" for="question_type">Tipe Soal <span style="color:var(--color-rose-500)">*</span></label>
                <select name="question_type" id="question_type" class="form-select @error('question_type') is-invalid @enderror" onchange="toggleQuestionType(this.value)" required>
                    <option value="single_choice" {{ old('question_type', request('type') == 'essay' ? 'essay' : 'single_choice') == 'single_choice' ? 'selected' : '' }}>Pilihan Ganda</option>
                    <option value="essay" {{ old('question_type', request('type') == 'essay' ? 'essay' : 'single_choice') == 'essay' ? 'selected' : '' }}>Essai / Uraian</option>
                </select>
                @error('question_type')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="flex: 1;" id="group_score_weight">
                <label class="form-label" for="score_weight" id="label_score_weight">Bobot Nilai <span style="color:var(--color-rose-500)">*</span></label>
                <input type="number" step="0.1" min="0.1" max="100" name="score_weight" id="score_weight" class="form-control @error('score_weight') is-invalid @enderror" value="{{ old('score_weight', '2.0') }}" required>
                @error('score_weight')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="flex: 1;">
                <label class="form-label" for="difficulty">Tingkat Kesulitan</label>
                <select name="difficulty" id="difficulty" class="form-select">
                    <option value="easy" {{ old('difficulty') == 'easy' ? 'selected' : '' }}>Mudah</option>
                    <option value="medium" {{ old('difficulty', 'medium') == 'medium' ? 'selected' : '' }}>Sedang</option>
                    <option value="hard" {{ old('difficulty') == 'hard' ? 'selected' : '' }}>Sulit</option>
                </select>
            </div>
        </div>
    </div>

    <!-- KARTU PERTANYAAN DENGAN EDITOR PERSIS GAMBAR TIGA -->
    <div class="card" style="margin-bottom: 20px;">
        <h3 style="font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0 0 14px 0;">
            Pertanyaan
        </h3>

        <!-- WYSIWYG EDITOR TOOLBAR TINYMCE PERSIS GAMBAR 3 -->
        <div class="tinymce-mock-container">
            <div class="tinymce-menubar">
                <span>File</span>
                <span>Edit</span>
                <span>View</span>
                <span>Insert</span>
                <span>Format</span>
                <span>Table</span>
            </div>
            <div class="tinymce-toolbar">
                <button type="button" class="tb-btn" title="Undo" onclick="formatDoc('undo')">&#8630;</button>
                <button type="button" class="tb-btn" title="Redo" onclick="formatDoc('redo')">&#8631;</button>
                <div class="tb-separator"></div>
                <button type="button" class="tb-btn" title="Format">Formats &#9662;</button>
                <div class="tb-separator"></div>
                <button type="button" class="tb-btn" title="Bold" onclick="formatDoc('bold')" style="font-weight: bold;">B</button>
                <button type="button" class="tb-btn" title="Italic" onclick="formatDoc('italic')" style="font-style: italic;">I</button>
                <div class="tb-separator"></div>
                <button type="button" class="tb-btn" title="Align Left" onclick="formatDoc('justifyLeft')">&#8801;</button>
                <button type="button" class="tb-btn" title="Align Center" onclick="formatDoc('justifyCenter')">&#8788;</button>
                <button type="button" class="tb-btn" title="Align Right" onclick="formatDoc('justifyRight')">&#8801;</button>
                <button type="button" class="tb-btn" title="Justify" onclick="formatDoc('justifyFull')">&#9776;</button>
                <div class="tb-separator"></div>
                <button type="button" class="tb-btn" title="Bullet List" onclick="formatDoc('insertUnorderedList')">&#8226;&#8801;</button>
                <button type="button" class="tb-btn" title="Numbered List" onclick="formatDoc('insertOrderedList')">1.&#8801;</button>
                <div class="tb-separator"></div>
                <button type="button" class="tb-btn" title="Outdent" onclick="formatDoc('outdent')">&#8676;</button>
                <button type="button" class="tb-btn" title="Indent" onclick="formatDoc('indent')">&#8677;</button>
                <div class="tb-separator"></div>
                <button type="button" class="tb-btn" title="Link" onclick="insertLink()">&#128279;</button>
                <button type="button" class="tb-btn" title="Image" onclick="insertImage()">&#128444;</button>
            </div>
            <textarea name="content" id="editor_content" class="form-control" rows="7" style="width: 100%; border: none; border-radius: 0; outline: none; padding: 14px; font-size: 14px; line-height: 1.6; resize: vertical;" placeholder="Tuliskan pertanyaan soal di sini..." oninput="updateWordCount(this.value)" required>{{ old('content') }}</textarea>
            <div class="tinymce-statusbar">
                <span id="word_count_status">0 WORDS &bull; POWERED BY TINYMCE</span>
            </div>
        </div>

        <!-- PERHATIAN TEXT SESUAI GAMBAR 3 & PANDUAN PENGGUNAAN -->
        <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid #f1f5f9; font-size: 13px; color: #475569;">
            <strong style="color: #1e293b; font-size: 13.5px; display: block; margin-bottom: 6px;">Perhatian:</strong>
            <p style="margin: 0 0 4px 0; line-height: 1.5;">Disini Anda bisa membuat soal ujian berbentuk pilihan ganda dan essai.</p>
            <p style="margin: 0 0 4px 0; line-height: 1.5;">Untuk membuat soal pilihan ganda, silahkan masukan soal / pertanyaan, pilihan jawaban beserta kunci jawabannya.</p>
            <p style="margin: 0 0 4px 0; line-height: 1.5;">Sedangkan untuk membuat soal essai, silahkan masukan soal / pertanyaan dan isi bagian bobot soal essai, sedangkan bagian pilihan A, B, C, D dan E nya wajib dikosongkan.</p>
            <p style="margin: 0; line-height: 1.5; color: #0284c7; font-weight: 600;">Bobot Essai diisi hanya ketika soal berbentuk essai.</p>
        </div>
    </div>

    <!-- SECTION PILIHAN JAWABAN (UNTUK PILIHAN GANDA) -->
    <div id="options-container" class="card" style="margin-bottom: 20px; background: #f8fafc; border: 1px solid #e2e8f0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
            <h3 style="font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0;">
                Pilihan Jawaban &amp; Kunci Jawaban
            </h3>
            <span style="font-size: 0.85rem; color: #64748b;">
                Pilih radio button di sebelah kiri untuk menentukan Kunci Jawaban Benar
            </span>
        </div>

        @php
            $labels = ['A', 'B', 'C', 'D', 'E'];
        @endphp

        @foreach($labels as $idx => $label)
            <div class="option-row" style="margin-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; min-width: 60px;">
                    <input type="radio" name="correct_option" value="{{ $label }}" {{ old('correct_option', 'A') == $label ? 'checked' : '' }} style="accent-color: #16a34a; width: 18px; height: 18px;">
                    <span class="option-badge" style="font-weight: 700; font-size: 13px; color: #1e293b;">{{ $label }}.</span>
                </label>
                <input type="hidden" name="options[{{ $idx }}][label]" value="{{ $label }}">
                <input type="text" name="options[{{ $idx }}][content]" id="opt_input_{{ strtolower($label) }}" class="form-control" placeholder="Teks pilihan jawaban {{ $label }}..." value="{{ old("options.{$idx}.content") }}">
            </div>
        @endforeach
    </div>

    <!-- KHUSUS ESSAI: BOBOT SOAL ESSAI NOTICE -->
    <div id="essay-weight-notice" class="card" style="display: none; margin-bottom: 20px; background: #fefce8; border: 1px solid #fef08a;">
        <div style="font-weight: 700; font-size: 14px; color: #854d0e; margin-bottom: 4px;">
            ✍️ Mode Soal Essai / Uraian
        </div>
        <p style="margin: 0; font-size: 13px; color: #a16207; line-height: 1.5;">
            Pada tipe soal essai, pilihan A s/d E otomatis dikosongkan. Siswa akan menjawab pertanyaan ini dalam bentuk esai/uraian teks bebas. Nilai akan dinilai oleh guru berdasarkan Bobot Soal Essai di atas.
        </p>
    </div>

    <div class="form-actions" style="display: flex; gap: 10px; margin-bottom: 30px;">
        <button type="submit" class="btn btn-primary" style="font-weight: 700; padding: 10px 28px; background: #0052cc; border-color: #0052cc;">
            Simpan Pertanyaan
        </button>
        <a href="{{ $backUrl }}" class="btn btn-secondary">Batal</a>
    </div>
</form>

<script>
    function updateWordCount(val) {
        var words = val.trim().split(/\s+/).filter(function(w) { return w.length > 0; }).length;
        var el = document.getElementById('word_count_status');
        if (el) {
            el.innerText = words + " WORDS \u2022 POWERED BY TINYMCE";
        }
    }

    function formatDoc(cmd, val) {
        var textarea = document.getElementById('editor_content');
        if (!textarea) return;
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var selected = textarea.value.substring(start, end);
        var replacement = selected;

        if (cmd === 'bold') {
            replacement = '**' + selected + '**';
        } else if (cmd === 'italic') {
            replacement = '*' + selected + '*';
        } else if (cmd === 'insertUnorderedList') {
            replacement = '\n- ' + selected;
        } else if (cmd === 'insertOrderedList') {
            replacement = '\n1. ' + selected;
        }

        if (replacement !== selected) {
            textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
            updateWordCount(textarea.value);
        }
    }

    function insertLink() {
        var url = prompt("Masukkan URL tautan:", "https://");
        if (url) {
            var textarea = document.getElementById('editor_content');
            if (textarea) textarea.value += ' ' + url + ' ';
        }
    }

    function insertImage() {
        var url = prompt("Masukkan URL Gambar:", "https://");
        if (url) {
            var textarea = document.getElementById('editor_content');
            if (textarea) textarea.value += ' ![Gambar](' + url + ') ';
        }
    }

    function toggleQuestionType(type) {
        var optContainer = document.getElementById('options-container');
        var essayNotice = document.getElementById('essay-weight-notice');
        var weightLabel = document.getElementById('label_score_weight');
        var weightInput = document.getElementById('score_weight');

        if (type === 'essay') {
            if (optContainer) optContainer.style.display = 'none';
            if (essayNotice) essayNotice.style.display = 'block';
            if (weightLabel) weightLabel.innerHTML = 'Bobot Soal Essai <span style="color:var(--color-rose-500)">*</span>';
            if (weightInput && weightInput.value == '2.0') weightInput.value = '20.0';

            // Kosongkan input opsi A-E sesuai panduan
            ['a', 'b', 'c', 'd', 'e'].forEach(function(l) {
                var inp = document.getElementById('opt_input_' + l);
                if (inp) inp.value = '';
            });
        } else {
            if (optContainer) optContainer.style.display = 'block';
            if (essayNotice) essayNotice.style.display = 'none';
            if (weightLabel) weightLabel.innerHTML = 'Bobot Nilai <span style="color:var(--color-rose-500)">*</span>';
            if (weightInput && weightInput.value == '20.0') weightInput.value = '2.0';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var typeSelect = document.getElementById('question_type');
        if (typeSelect) {
            toggleQuestionType(typeSelect.value);
        }
        var editor = document.getElementById('editor_content');
        if (editor) {
            updateWordCount(editor.value);
        }
    });
</script>
@endsection
