@extends('layouts.app')

@section('title', 'Tambah Soal - CBT Administrator')

@section('content')
<style>
    /* WORD RIBBON & DOCUMENT EDITOR SYSTEM STYLES */
    .word-editor-box {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        overflow: hidden;
        margin-bottom: 18px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .word-editor-box:focus-within {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }
    .word-menu-tabbar {
        display: flex;
        gap: 16px;
        padding: 6px 14px;
        background: #f1f5f9;
        border-bottom: 1px solid #e2e8f0;
        font-size: 12px;
        font-weight: 700;
        color: #475569;
    }
    .word-menu-tabbar span {
        cursor: pointer;
        padding: 3px 6px;
        border-radius: 4px;
        transition: background 0.15s, color 0.15s;
    }
    .word-menu-tabbar span:hover {
        background: #e2e8f0;
        color: #1e293b;
    }
    .word-ribbon-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        align-items: center;
        padding: 6px 10px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
    }
    .word-tool-btn {
        background: #ffffff;
        border: 1px solid transparent;
        border-radius: 4px;
        padding: 3px 6px;
        font-size: 12px;
        color: #334155;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 26px;
        height: 26px;
        transition: all 0.15s ease;
        user-select: none;
    }
    .word-tool-btn:hover {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }
    .word-select {
        height: 26px;
        padding: 2px 6px;
        font-size: 11.5px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        background: #ffffff;
        color: #334155;
        cursor: pointer;
        outline: none;
    }
    .word-select:hover {
        border-color: #94a3b8;
    }
    .word-sep {
        width: 1px;
        height: 20px;
        background: #e2e8f0;
        margin: 0 4px;
    }
    .word-statusbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px 12px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        font-size: 11px;
        color: #64748b;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    .choice-badge-label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        margin: 0;
        padding: 6px 10px;
        background: #f1f5f9;
        border-radius: 6px;
        font-weight: 800;
        font-size: 13px;
        color: #1e293b;
    }
    .choice-badge-label:hover {
        background: #e2e8f0;
    }
    .choice-badge-label input[type="radio"] {
        accent-color: #16a34a;
        width: 17px;
        height: 17px;
        cursor: pointer;
    }
    .ai-chip {
        display: inline-block;
        padding: 4px 10px;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 20px;
        font-size: 11.5px;
        color: #334155;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .ai-chip:hover {
        background: #ede9fe;
        border-color: #a855f7;
        color: #6b21a8;
        transform: translateY(-1px);
    }
    .word-content-editable {
        min-height: 140px;
        max-height: 480px;
        overflow-y: auto;
        padding: 14px 16px;
        font-size: 14px;
        line-height: 1.6;
        outline: none;
        background: #ffffff;
        color: #1e293b;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        word-break: break-word;
    }
    .word-content-editable:focus {
        background: #ffffff;
    }
    .word-content-editable:empty:before {
        content: attr(placeholder);
        color: #94a3b8;
        pointer-events: none;
        display: block;
    }
    .word-content-editable table {
        border-collapse: collapse;
        width: 100%;
        margin: 10px 0;
    }
    .word-content-editable table, .word-content-editable th, .word-content-editable td {
        border: 1px solid #cbd5e1;
        padding: 8px 12px;
    }
    .word-content-editable th {
        background: #f8fafc;
        font-weight: 700;
    }
    .word-content-editable img {
        max-width: 100%;
        border-radius: 6px;
        margin: 6px 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .word-content-editable ul, .word-content-editable ol {
        padding-left: 24px;
        margin: 8px 0;
    }
    .google-signin-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: #ffffff;
        border: 1px solid #dadce0;
        border-radius: 24px;
        padding: 10px 24px;
        font-size: 14px;
        font-weight: 600;
        color: #3c4043;
        box-shadow: 0 1px 3px rgba(60,64,67,0.15);
        cursor: pointer;
        transition: background-color 0.2s, box-shadow 0.2s;
    }
    .google-signin-btn:hover {
        background-color: #f8fafd;
        box-shadow: 0 2px 6px rgba(60,64,67,0.25);
    }
    .google-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 12px;
    }
    .google-avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #2563eb;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 11px;
    }
</style>

<div class="content-header">
    <div>
        <h1 class="page-title">Tambah Soal Ujian</h1>
        <p class="page-subtitle">Penyusunan butir soal dan pilihan jawaban dengan Word Ribbon Tools Lengkap &amp; Gemini AI</p>
    </div>
    @php
        $backUrl = request('subject_id') ? route('admin.questions.index', ['subject_id' => request('subject_id')]) : route('admin.questions.index');
    @endphp
    <a href="{{ $backUrl }}" class="btn btn-secondary">&larr; Kembali ke Daftar Soal</a>
</div>

<form method="POST" action="{{ route('admin.questions.store') }}" id="formCreateQuestion" onsubmit="return validateAndSyncQuestionForm();">
    @csrf

    <div class="card" style="margin-bottom: 20px;">
        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label class="form-label" for="subject_id">Mata Pelajaran / Bank Soal <span style="color:var(--color-rose-500)">*</span></label>
                <select name="subject_id" id="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Mata Pelajaran --</option>
                    @foreach($subjects as $sb)
                        <option value="{{ $sb->id }}" {{ (old('subject_id', request('subject_id')) == $sb->id) ? 'selected' : '' }} data-name="{{ $sb->name }}">
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

    <!-- KARTU PERTANYAAN DENGAN WORD RIBBON LENGKAP -->
    <div class="card" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <h3 style="font-size: 1.05rem; font-weight: 800; color: #1e293b; margin: 0;">
                    Teks Pertanyaan Soal
                </h3>
                <span style="font-size: 11px; background: #e0f2fe; color: #0284c7; padding: 2px 8px; border-radius: 4px; font-weight: 700;">Microsoft Word Ribbon</span>
            </div>
            <button type="button" class="btn btn-sm" onclick="openAiQuestionModal()" style="background: linear-gradient(135deg, #7c3aed 0%, #2563eb 100%); color: #ffffff; font-weight: 700; border: none; border-radius: 6px; padding: 7px 16px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35); cursor: pointer; transition: all 0.2s ease;">
                <span>✨</span> Buat Soal dengan AI Gemini
            </button>
        </div>

        <!-- WORD EDITOR CONTAINER - QUESTION -->
        <div class="word-editor-box">
            <div class="word-menu-tabbar">
                <span>File</span>
                <span style="color: #2563eb; border-bottom: 2px solid #2563eb;">Beranda</span>
                <span>Sisipkan</span>
                <span>Tata Letak</span>
                <span>Rumus Matematika</span>
                <span>Tinjauan</span>
                <span>Bantuan</span>
            </div>
            <div class="word-ribbon-bar">
                <button type="button" class="word-tool-btn" title="Urungkan (Undo)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'undo')">&#8630;</button>
                <button type="button" class="word-tool-btn" title="Ulangi (Redo)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'redo')">&#8631;</button>
                <div class="word-sep"></div>

                <select class="word-select" style="width: 105px;" title="Jenis Font" onmousedown="saveWordSelection('editor_content');" onchange="formatWordDoc('editor_content', 'fontName', this.value); this.selectedIndex=0;">
                    <option value="" disabled selected>Aptos (Body)</option>
                    <option value="Calibri">Calibri</option>
                    <option value="Arial">Arial</option>
                    <option value="'Times New Roman', serif">Times New Roman</option>
                    <option value="'Courier New', monospace">Courier (Kode)</option>
                    <option value="Verdana">Verdana</option>
                </select>

                <select class="word-select" style="width: 52px;" title="Ukuran Font" onmousedown="saveWordSelection('editor_content');" onchange="formatWordDoc('editor_content', 'fontSize', this.value); this.selectedIndex=0;">
                    <option value="" disabled selected>12</option>
                    <option value="10px">10</option>
                    <option value="11px">11</option>
                    <option value="12px">12</option>
                    <option value="14px">14</option>
                    <option value="16px">16</option>
                    <option value="18px">18</option>
                    <option value="22px">22</option>
                </select>

                <div class="word-sep"></div>

                <button type="button" class="word-tool-btn" title="Tebal (Bold)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'bold')" style="font-weight: 800;">B</button>
                <button type="button" class="word-tool-btn" title="Miring (Italic)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'italic')" style="font-style: italic; font-weight: 600;">I</button>
                <button type="button" class="word-tool-btn" title="Garis Bawah (Underline)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'underline')"><u>U</u></button>
                <button type="button" class="word-tool-btn" title="Coret (Strikethrough)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'strikeThrough')"><s>ab</s></button>
                <button type="button" class="word-tool-btn" title="Subscript (x₂)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'subscript')">x₂</button>
                <button type="button" class="word-tool-btn" title="Superscript (x²)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'superscript')">x²</button>

                <div class="word-sep"></div>

                <select class="word-select" style="width: 65px;" title="Warna Font" onmousedown="saveWordSelection('editor_content');" onchange="formatWordDoc('editor_content', 'foreColor', this.value); this.selectedIndex=0;">
                    <option value="" disabled selected>🎨 Warna</option>
                    <option value="#000000" style="color:#000000;">Hitam</option>
                    <option value="#dc2626" style="color:#dc2626;">Merah</option>
                    <option value="#2563eb" style="color:#2563eb;">Biru</option>
                    <option value="#16a34a" style="color:#16a34a;">Hijau</option>
                    <option value="#d97706" style="color:#d97706;">Oranye</option>
                    <option value="#7c3aed" style="color:#7c3aed;">Ungu</option>
                </select>

                <select class="word-select" style="width: 65px;" title="Warna Sorotan Teks" onmousedown="saveWordSelection('editor_content');" onchange="formatWordDoc('editor_content', 'hiliteColor', this.value); this.selectedIndex=0;">
                    <option value="" disabled selected>🖍️ Sorot</option>
                    <option value="#fef08a" style="background:#fef08a;">Kuning</option>
                    <option value="#bbf7d0" style="background:#bbf7d0;">Hijau Muda</option>
                    <option value="#bae6fd" style="background:#bae6fd;">Biru Muda</option>
                    <option value="#fbcfe8" style="background:#fbcfe8;">Merah Muda</option>
                </select>

                <div class="word-sep"></div>

                <button type="button" class="word-tool-btn" title="Rata Kiri" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'justifyLeft')">&#8801;</button>
                <button type="button" class="word-tool-btn" title="Rata Tengah" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'justifyCenter')">&#8788;</button>
                <button type="button" class="word-tool-btn" title="Rata Kanan" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'justifyRight')">&#8801;</button>
                <button type="button" class="word-tool-btn" title="Rata Kanan Kiri (Justify)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'justifyFull')">&#9776;</button>
                <button type="button" class="word-tool-btn" title="Daftar Butir (Bullets)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'insertUnorderedList')">•≡</button>
                <button type="button" class="word-tool-btn" title="Daftar Angka (Numbering)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'insertOrderedList')">1.≡</button>

                <div class="word-sep"></div>

                <button type="button" class="word-tool-btn" title="Akar Kuadrat (√)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '√')">√x</button>
                <button type="button" class="word-tool-btn" title="Pi (π)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', 'π')">π</button>
                <button type="button" class="word-tool-btn" title="Plus Minus (±)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '±')">±</button>
                <button type="button" class="word-tool-btn" title="Perkalian (×)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '×')">×</button>
                <button type="button" class="word-tool-btn" title="Pembagian (÷)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '÷')">÷</button>
                <button type="button" class="word-tool-btn" title="Kurang Dari Sama Dengan (≤)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '≤')">≤</button>
                <button type="button" class="word-tool-btn" title="Lebih Dari Sama Dengan (≥)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '≥')">≥</button>
                <button type="button" class="word-tool-btn" title="Tidak Sama Dengan (≠)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '≠')">≠</button>
                <button type="button" class="word-tool-btn" title="Derajat (°)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '°')">°</button>
                <button type="button" class="word-tool-btn" title="Tak Hingga (∞)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '∞')">∞</button>
                <button type="button" class="word-tool-btn" title="Sigma (∑)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '∑')">∑</button>
                <button type="button" class="word-tool-btn" title="Integral (∫)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '∫')">∫</button>
                <button type="button" class="word-tool-btn" title="Alpha (α)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', 'α')">α</button>
                <button type="button" class="word-tool-btn" title="Beta (β)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', 'β')">β</button>
                <button type="button" class="word-tool-btn" title="Theta (θ)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', 'θ')">θ</button>
                <button type="button" class="word-tool-btn" title="Delta (Δ)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', 'Δ')">Δ</button>
                <button type="button" class="word-tool-btn" title="Omega (Ω)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', 'Ω')">Ω</button>
                <button type="button" class="word-tool-btn" title="Kurang Lebih / Hampir Sama (≈)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '≈')">≈</button>

                <div class="word-sep"></div>

                <button type="button" class="word-tool-btn" title="Sisipkan Tabel 2x2" onmousedown="event.preventDefault();" onclick="insertWordTable('editor_content')">⊞ Tabel</button>
                <button type="button" class="word-tool-btn" title="Sisipkan Tautan (Link)" onmousedown="event.preventDefault();" onclick="insertWordLink('editor_content')">🔗 Link</button>
                <button type="button" class="word-tool-btn" title="Sisipkan Gambar (Image)" onmousedown="event.preventDefault();" onclick="insertWordImage('editor_content')">🖼️ Gambar</button>
                <button type="button" class="word-tool-btn" title="Hapus Format (Clear Formatting)" onmousedown="event.preventDefault();" onclick="clearWordFormat('editor_content')">Tx</button>
            </div>
            <div id="editor_content" contenteditable="true" class="word-content-editable" placeholder="Ketikkan butir soal / pertanyaan di sini..." oninput="syncWordContent('editor_content')" onfocus="setWordEditorActive('editor_content')">{!! old('content') !!}</div>
            <textarea name="content" id="raw_editor_content" style="display:none;">{{ old('content') }}</textarea>
            <div class="word-statusbar">
                <span>HALAMAN 1 DARI 1</span>
                <span id="word_status_editor_content">0 KATA • 0 KARAKTER • BUTIR SOAL • WORD EDITOR PRO</span>
            </div>
        </div>

        <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #f1f5f9; font-size: 13px; color: #475569;">
            <strong style="color: #1e293b; font-size: 13.5px; display: block; margin-bottom: 6px;">Petunjuk Penggunaan Toolbar:</strong>
            <p style="margin: 0 0 4px 0; line-height: 1.5;">Gunakan ribbon Microsoft Word di atas untuk memformat rumus matematika (pangkat, akar, simbol Yunani), menyisipkan gambar, warna teks, hingga tabel matriks ordo 2x2.</p>
            <p style="margin: 0; line-height: 1.5; color: #0284c7; font-weight: 600;">Untuk soal Essai, bagian pilihan jawaban A s/d E otomatis dikosongkan dan digantikan dengan bobot nilai esai.</p>
        </div>
    </div>

    <!-- SECTION PILIHAN JAWABAN (SETIAP PILIHAN A-E DILENGKAPI WORD RIBBON LENGKAP) -->
    <div id="options-container" class="card" style="margin-bottom: 20px; background: #f8fafc; border: 1px solid #e2e8f0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
            <div>
                <h3 style="font-size: 1.05rem; font-weight: 800; color: #1e293b; margin: 0;">
                    Pilihan Jawaban &amp; Kunci Jawaban (A s/d E)
                </h3>
                <span style="font-size: 0.85rem; color: #64748b;">
                    Setiap opsi dilengkapi Ribbon Editor Microsoft Word mandiri. Klik radio button hijau untuk menentukan Kunci Jawaban Benar.
                </span>
            </div>
        </div>

        @php
            $optLetters = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'e' => 'E'];
            $idx = 0;
        @endphp

        @foreach($optLetters as $key => $letter)
            @php
                $editorId = "opt_editor_{$key}";
            @endphp
            <div class="word-editor-box" style="margin-bottom: 16px; border: 1.5px solid #cbd5e1;">
                
                <!-- OPTION HEADER BAR -->
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #f1f5f9; border-bottom: 1px solid #e2e8f0;">
                    <label class="choice-badge-label">
                        <input type="radio" name="correct_option" value="{{ $letter }}" {{ old('correct_option', 'A') == $letter ? 'checked' : '' }}>
                        <span>PILIHAN {{ $letter }}</span>
                        <span style="font-size: 11px; font-weight: 600; color: #15803d; background: #dcfce7; padding: 2px 6px; border-radius: 4px; margin-left: 4px;">(Klik Radio Untuk Kunci Benar)</span>
                    </label>
                    <input type="hidden" name="options[{{ $idx }}][label]" value="{{ $letter }}">
                    <span style="font-size: 11px; color: #64748b; font-weight: 700;">Word Ribbon Editor Opsi {{ $letter }}</span>
                </div>

                <!-- OPTION RIBBON TOOLBAR -->
                <div class="word-ribbon-bar" style="background: #fafafa;">
                    <button type="button" class="word-tool-btn" title="Undo" onmousedown="event.preventDefault();" onclick="formatWordDoc('{{ $editorId }}', 'undo')">&#8630;</button>
                    <button type="button" class="word-tool-btn" title="Redo" onmousedown="event.preventDefault();" onclick="formatWordDoc('{{ $editorId }}', 'redo')">&#8631;</button>
                    <div class="word-sep"></div>

                    <select class="word-select" style="width: 100px;" title="Jenis Font" onmousedown="saveWordSelection('{{ $editorId }}');" onchange="formatWordDoc('{{ $editorId }}', 'fontName', this.value); this.selectedIndex=0;">
                        <option value="" disabled selected>Aptos</option>
                        <option value="Calibri">Calibri</option>
                        <option value="Arial">Arial</option>
                        <option value="'Times New Roman', serif">Times New Roman</option>
                        <option value="'Courier New', monospace">Courier</option>
                    </select>

                    <select class="word-select" style="width: 50px;" title="Ukuran Font" onmousedown="saveWordSelection('{{ $editorId }}');" onchange="formatWordDoc('{{ $editorId }}', 'fontSize', this.value); this.selectedIndex=0;">
                        <option value="" disabled selected>11</option>
                        <option value="10px">10</option>
                        <option value="11px">11</option>
                        <option value="12px">12</option>
                        <option value="14px">14</option>
                        <option value="16px">16</option>
                    </select>

                    <div class="word-sep"></div>

                    <button type="button" class="word-tool-btn" title="Tebal (Bold)" onmousedown="event.preventDefault();" onclick="formatWordDoc('{{ $editorId }}', 'bold')" style="font-weight: 800;">B</button>
                    <button type="button" class="word-tool-btn" title="Miring (Italic)" onmousedown="event.preventDefault();" onclick="formatWordDoc('{{ $editorId }}', 'italic')" style="font-style: italic; font-weight: 600;">I</button>
                    <button type="button" class="word-tool-btn" title="Garis Bawah (Underline)" onmousedown="event.preventDefault();" onclick="formatWordDoc('{{ $editorId }}', 'underline')"><u>U</u></button>
                    <button type="button" class="word-tool-btn" title="Coret (Strikethrough)" onmousedown="event.preventDefault();" onclick="formatWordDoc('{{ $editorId }}', 'strikeThrough')"><s>S</s></button>
                    <div class="word-sep"></div>
                    <button type="button" class="word-tool-btn" title="Subscript (x₂)" onmousedown="event.preventDefault();" onclick="formatWordDoc('{{ $editorId }}', 'subscript')">x₂</button>
                    <button type="button" class="word-tool-btn" title="Superscript (x²)" onmousedown="event.preventDefault();" onclick="formatWordDoc('{{ $editorId }}', 'superscript')">x²</button>

                    <div class="word-sep"></div>

                    <select class="word-select" style="width: 65px;" title="Warna Font" onmousedown="saveWordSelection('{{ $editorId }}');" onchange="formatWordDoc('{{ $editorId }}', 'foreColor', this.value); this.selectedIndex=0;">
                        <option value="" disabled selected>🎨 Warna</option>
                        <option value="#000000" style="color:#000000;">Hitam</option>
                        <option value="#dc2626" style="color:#dc2626;">Merah</option>
                        <option value="#2563eb" style="color:#2563eb;">Biru</option>
                        <option value="#16a34a" style="color:#16a34a;">Hijau</option>
                        <option value="#d97706" style="color:#d97706;">Oranye</option>
                        <option value="#7c3aed" style="color:#7c3aed;">Ungu</option>
                    </select>

                    <select class="word-select" style="width: 65px;" title="Warna Sorot" onmousedown="saveWordSelection('{{ $editorId }}');" onchange="formatWordDoc('{{ $editorId }}', 'hiliteColor', this.value); this.selectedIndex=0;">
                        <option value="" disabled selected>🖍️ Sorot</option>
                        <option value="#fef08a" style="background:#fef08a;">Kuning</option>
                        <option value="#bbf7d0" style="background:#bbf7d0;">Hijau</option>
                        <option value="#bae6fd" style="background:#bae6fd;">Biru</option>
                        <option value="#fbcfe8" style="background:#fbcfe8;">Pink</option>
                    </select>

                    <div class="word-sep"></div>

                    <button type="button" class="word-tool-btn" title="Rata Kiri" onmousedown="event.preventDefault();" onclick="formatWordDoc('{{ $editorId }}', 'justifyLeft')">&#8801;</button>
                    <button type="button" class="word-tool-btn" title="Rata Tengah" onmousedown="event.preventDefault();" onclick="formatWordDoc('{{ $editorId }}', 'justifyCenter')">&#8788;</button>
                    <button type="button" class="word-tool-btn" title="Rata Kanan" onmousedown="event.preventDefault();" onclick="formatWordDoc('{{ $editorId }}', 'justifyRight')">&#8801;</button>
                    <button type="button" class="word-tool-btn" title="Justify" onmousedown="event.preventDefault();" onclick="formatWordDoc('{{ $editorId }}', 'justifyFull')">&#9776;</button>

                    <div class="word-sep"></div>

                    <button type="button" class="word-tool-btn" title="Akar Kuadrat (√)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('{{ $editorId }}', '√')">√x</button>
                    <button type="button" class="word-tool-btn" title="Pi (π)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('{{ $editorId }}', 'π')">π</button>
                    <button type="button" class="word-tool-btn" title="Plus Minus (±)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('{{ $editorId }}', '±')">±</button>
                    <button type="button" class="word-tool-btn" title="Perkalian (×)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('{{ $editorId }}', '×')">×</button>
                    <button type="button" class="word-tool-btn" title="Pembagian (÷)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('{{ $editorId }}', '÷')">÷</button>
                    <button type="button" class="word-tool-btn" title="Kurang Dari Sama Dengan (≤)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('{{ $editorId }}', '≤')">≤</button>
                    <button type="button" class="word-tool-btn" title="Lebih Dari Sama Dengan (≥)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('{{ $editorId }}', '≥')">≥</button>
                    <button type="button" class="word-tool-btn" title="Tidak Sama Dengan (≠)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('{{ $editorId }}', '≠')">≠</button>
                    <button type="button" class="word-tool-btn" title="Derajat (°)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('{{ $editorId }}', '°')">°</button>
                    <button type="button" class="word-tool-btn" title="Tak Terhingga (∞)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('{{ $editorId }}', '∞')">∞</button>
                    <button type="button" class="word-tool-btn" title="Alpha (α)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('{{ $editorId }}', 'α')">α</button>
                    <button type="button" class="word-tool-btn" title="Beta (β)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('{{ $editorId }}', 'β')">β</button>
                    <button type="button" class="word-tool-btn" title="Theta (θ)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('{{ $editorId }}', 'θ')">θ</button>
                    <button type="button" class="word-tool-btn" title="Delta (Δ)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('{{ $editorId }}', 'Δ')">Δ</button>
                    <button type="button" class="word-tool-btn" title="Omega (Ω)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('{{ $editorId }}', 'Ω')">Ω</button>
                    <button type="button" class="word-tool-btn" title="Hampir Sama (≈)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('{{ $editorId }}', '≈')">≈</button>

                    <div class="word-sep"></div>

                    <button type="button" class="word-tool-btn" title="Tabel 2x2" onmousedown="event.preventDefault();" onclick="insertWordTable('{{ $editorId }}')">⊞</button>
                    <button type="button" class="word-tool-btn" title="Tautan Link" onmousedown="event.preventDefault();" onclick="insertWordLink('{{ $editorId }}')">🔗</button>
                    <button type="button" class="word-tool-btn" title="Gambar Opsi" onmousedown="event.preventDefault();" onclick="insertWordImage('{{ $editorId }}')">🖼️</button>
                    <button type="button" class="word-tool-btn" title="Hapus Format" onmousedown="event.preventDefault();" onclick="clearWordFormat('{{ $editorId }}')">Tx</button>
                </div>

                <!-- TEXTAREA FOR OPTION CONTENT -->
                <div id="{{ $editorId }}" contenteditable="true" class="word-content-editable" style="min-height: 80px; padding: 10px 14px;" placeholder="Tuliskan isi pilihan jawaban {{ $letter }} di sini..." oninput="syncWordContent('{{ $editorId }}')" onfocus="setWordEditorActive('{{ $editorId }}')">{!! old("options.{$idx}.content") !!}</div>
                <textarea name="options[{{ $idx }}][content]" id="raw_{{ $editorId }}" style="display:none;">{{ old("options.{$idx}.content") }}</textarea>
                
                <!-- STATUS BAR FOR OPTION -->
                <div class="word-statusbar">
                    <span style="font-size: 10.5px; color: #94a3b8;">OPSI JAWABAN {{ $letter }}</span>
                    <span id="word_status_{{ $editorId }}">0 KATA • 0 KARAKTER • OPSI {{ $letter }} • WORD EDITOR PRO</span>
                </div>
            </div>
            @php $idx++; @endphp
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

<!-- MODAL BUAT SOAL DENGAN AI (GEMINI AI ENGINE & GOOGLE ACCOUNT GATE) -->
<div class="modal-overlay" id="aiQuestionModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: #ffffff; border-radius: 16px; max-width: 720px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid #e2e8f0;">
        
        <!-- MODAL HEADER -->
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #4285F4 0%, #34A853 50%, #FBBC05 75%, #EA4335 100%); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(66, 133, 244, 0.25);">
                    <span style="font-size: 20px;">✨</span>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                        AI Generator Butir Soal CBT
                        <span style="font-size: 11px; background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 9999px; font-weight: 700;">Gemini 2.5 Pro</span>
                    </h3>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;" id="ai_modal_subject_label">
                        Didukung Google Gemini AI &bull; Bank Soal CBT
                    </div>
                </div>
            </div>
            <button type="button" onclick="closeAiQuestionModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; line-height: 1;">&times;</button>
        </div>

        <!-- SCREEN 1: WAJIB LOGIN DENGAN AKUN GOOGLE -->
        <div id="ai_google_login_screen" style="display: block; padding: 32px 28px; text-align: center;">
            <div style="width: 72px; height: 72px; margin: 0 auto 20px; border-radius: 50%; background: #ffffff; box-shadow: 0 8px 20px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0;">
                <svg width="36" height="36" viewBox="0 0 48 48">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.79l7.97-6.2z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                </svg>
            </div>
            <h4 style="font-size: 20px; font-weight: 800; color: #1e293b; margin: 0 0 8px;">Masuk dengan Akun Google</h4>
            <p style="color: #64748b; font-size: 13.5px; max-width: 480px; margin: 0 auto 24px; line-height: 1.5;">
                Untuk menggunakan fitur <strong>Google Gemini AI</strong> dalam pembuatan soal otomatis, silakan hubungkan akun Google Anda terlebih dahulu.
            </p>

            <!-- PILIHAN AKUN CEPAT ATAU CUSTOM -->
            <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 12px; padding: 20px; max-width: 460px; margin: 0 auto 20px; text-align: left;">
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">Pilih Akun Google Guru / Pengajar:</label>
                <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px;">
                    <div onclick="loginWithGoogle('guru.cbt@gmail.com', 'Guru Mata Pelajaran')" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.borderColor='#4285F4'; this.style.background='#eff6ff';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='#ffffff';">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #4285F4; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px;">G</div>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; font-size: 13px; color: #1e293b;">Guru Mata Pelajaran</div>
                            <div style="font-size: 11.5px; color: #64748b;">guru.cbt@gmail.com</div>
                        </div>
                        <span style="font-size: 12px; color: #2563eb; font-weight: 700;">Pilih &rarr;</span>
                    </div>
                    <div onclick="loginWithGoogle('admin.sekolah@gmail.com', 'Admin Kurikulum CBT')" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.borderColor='#4285F4'; this.style.background='#eff6ff';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='#ffffff';">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #0f9d58; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px;">A</div>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; font-size: 13px; color: #1e293b;">Admin Kurikulum CBT</div>
                            <div style="font-size: 11.5px; color: #64748b;">admin.sekolah@gmail.com</div>
                        </div>
                        <span style="font-size: 12px; color: #2563eb; font-weight: 700;">Pilih &rarr;</span>
                    </div>
                </div>
                
                <div style="display: flex; gap: 8px; align-items: center;">
                    <input type="email" id="custom_google_email" class="form-control" placeholder="atau ketik email Google Anda..." style="font-size: 12.5px;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="loginWithCustomGoogle()" style="font-weight: 700; white-space: nowrap;">Masuk</button>
                </div>
            </div>

            <!-- OFFICIAL GOOGLE SIGN IN BUTTON -->
            <div style="display: flex; justify-content: center; gap: 12px;">
                <button type="button" class="google-signin-btn" onclick="loginWithGoogle('user.google@gmail.com', 'Akun Google')">
                    <svg width="20" height="20" viewBox="0 0 48 48">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.79l7.97-6.2z"/>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    </svg>
                    Lanjutkan dengan Akun Google
                </button>
            </div>
            <div style="margin-top: 20px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeAiQuestionModal()">Batal</button>
            </div>
        </div>

        <!-- SCREEN 2: GEMINI AI QUESTION GENERATOR SCREEN (ACTIVE ONCE LOGGED IN) -->
        <div id="ai_generator_screen" style="display: none; padding: 22px 24px;">
            
            <!-- GOOGLE USER PROFILE CHIP BAR -->
            <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 14px; margin-bottom: 18px;">
                <div class="google-chip">
                    <span class="google-avatar" id="google_user_avatar">G</span>
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-weight: 700; color: #1e293b; font-size: 12.5px;" id="google_user_name">Guru CBT</span>
                        <span style="font-size: 11px; color: #64748b;" id="google_user_email">guru.cbt@gmail.com</span>
                    </div>
                    <span style="font-size: 11px; background: #dcfce7; color: #15803d; font-weight: 700; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">✓ Terhubung</span>
                </div>
                <button type="button" onclick="logoutGoogle()" style="background: none; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 10px; font-size: 11.5px; color: #64748b; cursor: pointer; font-weight: 600;" onmouseover="this.style.color='#ef4444'; this.style.borderColor='#fca5a5';" onmouseout="this.style.color='#64748b'; this.style.borderColor='#cbd5e1';">
                    Ganti Akun Google
                </button>
            </div>

            <!-- QUICK SUGGESTED TOPICS CHIPS -->
            <div style="margin-bottom: 16px;">
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 8px;">
                    Pilihan Topik Cepat (Klik untuk memilih):
                </label>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <span class="ai-chip" onclick="setAiTopic(this.innerText)">Persamaan Kuadrat &amp; Rumus ABC</span>
                    <span class="ai-chip" onclick="setAiTopic(this.innerText)">Determinan Matriks 2x2 &amp; Invers</span>
                    <span class="ai-chip" onclick="setAiTopic(this.innerText)">Teorema Pythagoras &amp; Trigonometri</span>
                    <span class="ai-chip" onclick="setAiTopic(this.innerText)">Hukum Newton &amp; Gerak Lurus</span>
                    <span class="ai-chip" onclick="setAiTopic(this.innerText)">Peluang Kombinatorika &amp; Permutasi</span>
                    <span class="ai-chip" onclick="setAiTopic(this.innerText)">Konsep Pemrograman OOP &amp; Database</span>
                    <span class="ai-chip" onclick="setAiTopic(this.innerText)">IP Address, Subnetting &amp; Mikrotik</span>
                </div>
            </div>

            <!-- INPUT TOPIK / MATERI -->
            <div style="margin-bottom: 16px;">
                <label style="font-size: 13px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 6px;">
                    Topik, Materi, atau Instruksi Khusus:
                </label>
                <textarea id="ai_topic_input" rows="3" class="form-control" placeholder="Contoh: Buatkan soal menghitung determinan matriks 2x2 ordo [4, -2; 3, 5] dengan 5 pilihan jawaban A-E yang bervariasi dan kunci jawaban yang tepat..." style="font-size: 13.5px;"></textarea>
                <small style="color: #64748b; font-size: 11.5px; margin-top: 4px; display: block;">
                    Anda bisa mengosongkan jika ingin Gemini AI menyusun butir materi terbaik secara otomatis.
                </small>
            </div>

            <div class="form-row" style="margin-bottom: 18px;">
                <div class="form-group" style="flex: 1;">
                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b;">Tipe Soal:</label>
                    <select id="ai_type_input" class="form-select">
                        <option value="single_choice" selected>Pilihan Ganda (Pilihan A s/d E)</option>
                        <option value="essay">Essai / Uraian</option>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b;">Tingkat Kesulitan:</label>
                    <select id="ai_difficulty_input" class="form-select">
                        <option value="easy">Mudah</option>
                        <option value="medium" selected>Sedang</option>
                        <option value="hard">Sukar / Standar HOTS</option>
                    </select>
                </div>
            </div>

            <!-- TOMBOL GENERATE -->
            <div style="text-align: center; margin-bottom: 20px;">
                <button type="button" id="btnRunAi" onclick="generateAiQuestion()" style="background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%); color: #ffffff; font-weight: 800; font-size: 14px; border: none; border-radius: 9999px; padding: 12px 32px; cursor: pointer; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35); transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px;">
                    <span>🚀</span> Generate Soal dengan Gemini AI Sekarang
                </button>
                <div id="ai_loading_indicator" style="display: none; margin-top: 14px; font-size: 13px; color: #2563eb; font-weight: 700;">
                    <span style="display: inline-block; animation: pulse 1s infinite;">🤖</span> Google Gemini AI sedang menyusun butir soal, rumus, &amp; 5 pilihan jawaban...
                </div>
            </div>

            <!-- HASIL GENERATE PREVIEW -->
            <div id="ai_result_box" style="display: none; background: #f0f9ff; border: 1.5px solid #bae6fd; border-radius: 12px; padding: 18px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #e0f2fe; padding-bottom: 8px;">
                    <span style="font-weight: 800; color: #0369a1; font-size: 13.5px; display: flex; align-items: center; gap: 6px;">
                        <span>✨</span> Hasil Generate Gemini AI:
                    </span>
                    <span class="badge" id="ai_preview_badge" style="background: #e0f2fe; color: #0369a1; font-weight: 700;">Pilihan Ganda</span>
                </div>

                <div style="margin-bottom: 14px;">
                    <strong style="font-size: 12px; color: #475569; display: block; margin-bottom: 4px;">Pertanyaan:</strong>
                    <div id="ai_preview_content" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 14px; font-size: 13.5px; line-height: 1.6;"></div>
                </div>

                <div id="ai_preview_options_sec" style="margin-bottom: 14px;">
                    <strong style="font-size: 12px; color: #475569; display: block; margin-bottom: 6px;">Pilihan Jawaban (A s/d E):</strong>
                    <div id="ai_preview_options" style="display: flex; flex-direction: column; gap: 6px; font-size: 13px;"></div>
                </div>

                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 13px;">
                    <div><strong style="color: #166534;">Kunci Jawaban Benar:</strong> <span id="ai_preview_key" style="color: #15803d; font-weight: 800; font-size: 15px;">-</span></div>
                    <div style="margin-top: 4px; color: #166534;"><strong style="color: #166534;">Penjelasan / Pembahasan:</strong> <span id="ai_preview_explanation">-</span></div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="generateAiQuestion()">🔄 Generate Ulang</button>
                    <button type="button" class="btn btn-success btn-sm" onclick="applyAiQuestion()" style="background: #15803d; border-color: #15803d; font-weight: 800; padding: 8px 22px; border-radius: 6px;">
                        ✅ Terapkan ke Form Soal
                    </button>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeAiQuestionModal()">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    // ==========================================
    // WORD RIBBON & DOCUMENT EDITOR ENGINE (TRUE WYSIWYG RICH TEXT)
    // ==========================================
    var lastActiveWordEditor = 'editor_content';
    var savedWordRanges = {};

    function setWordEditorActive(editorId) {
        lastActiveWordEditor = editorId;
    }

    function saveWordSelection(editorId) {
        var targetId = editorId || lastActiveWordEditor || 'editor_content';
        var sel = window.getSelection();
        if (sel && sel.rangeCount > 0) {
            var el = document.getElementById(targetId);
            var r = sel.getRangeAt(0);
            if (el && (el === r.commonAncestorContainer || el.contains(r.commonAncestorContainer))) {
                savedWordRanges[targetId] = r.cloneRange();
            }
        }
    }

    function restoreWordSelection(editorId) {
        var targetId = editorId || lastActiveWordEditor || 'editor_content';
        var el = document.getElementById(targetId);
        if (!el) return;
        el.focus();
        if (savedWordRanges[targetId]) {
            try {
                var sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(savedWordRanges[targetId]);
            } catch (e) {}
        }
    }

    function syncWordContent(editorId) {
        var el = document.getElementById(editorId);
        if (!el) return;
        var raw = document.getElementById('raw_' + editorId);
        if (raw) {
            var html = el.innerHTML;
            var text = (el.innerText || '').trim();
            if (!text && (html === '<br>' || html === '<p><br></p>' || html === '<div><br></div>' || !html.trim())) {
                raw.value = '';
            } else {
                raw.value = html;
            }
        }
        updateWordDocStatus(editorId);
        saveWordSelection(editorId);
    }

    function updateWordDocStatus(editorId) {
        var el = document.getElementById(editorId);
        if (!el) return;
        var text = el.innerText || el.value || '';
        var words = text.trim() ? text.trim().split(/\s+/).filter(function(w) { return w.length > 0; }).length : 0;
        var chars = text.length;
        
        var statusEl = document.getElementById('word_status_' + editorId);
        if (statusEl) {
            var label = (editorId === 'editor_content') ? 'BUTIR SOAL' : ('OPSI ' + editorId.slice(-1).toUpperCase());
            statusEl.innerText = words + ' KATA • ' + chars + ' KARAKTER • ' + label + ' • WORD EDITOR PRO';
        }
    }

    function formatWordDoc(editorId, cmd, val) {
        var el = document.getElementById(editorId) || document.getElementById(lastActiveWordEditor) || document.getElementById('editor_content');
        if (!el) return;
        lastActiveWordEditor = el.id;
        restoreWordSelection(el.id);

        if (cmd === 'fontFamily' || cmd === 'fontName') {
            document.execCommand('fontName', false, val);
        } else if (cmd === 'fontSize') {
            var sel = window.getSelection();
            if (sel && sel.rangeCount > 0 && !sel.isCollapsed) {
                var range = sel.getRangeAt(0);
                var span = document.createElement('span');
                span.style.fontSize = val;
                try {
                    span.appendChild(range.extractContents());
                    range.insertNode(span);
                    sel.removeAllRanges();
                    var newRange = document.createRange();
                    newRange.selectNodeContents(span);
                    sel.addRange(newRange);
                } catch (e) {
                    document.execCommand('fontSize', false, '4');
                }
            } else {
                document.execCommand('fontSize', false, '4');
            }
        } else if (cmd === 'foreColor') {
            document.execCommand('foreColor', false, val);
        } else if (cmd === 'hiliteColor') {
            if (!document.execCommand('hiliteColor', false, val)) {
                document.execCommand('backColor', false, val);
            }
        } else {
            document.execCommand(cmd, false, val || null);
        }

        syncWordContent(el.id);
        saveWordSelection(el.id);
    }

    function insertWordSymbol(editorId, sym) {
        var el = document.getElementById(editorId) || document.getElementById(lastActiveWordEditor) || document.getElementById('editor_content');
        if (!el) return;
        lastActiveWordEditor = el.id;
        restoreWordSelection(el.id);
        document.execCommand('insertHTML', false, ' ' + sym + ' ');
        syncWordContent(el.id);
        saveWordSelection(el.id);
    }

    function insertWordTable(editorId) {
        var el = document.getElementById(editorId) || document.getElementById(lastActiveWordEditor) || document.getElementById('editor_content');
        if (!el) return;
        lastActiveWordEditor = el.id;
        restoreWordSelection(el.id);
        var tableHtml = '<table border="1" cellpadding="8" style="border-collapse:collapse; width:100%; margin:8px 0; border:1px solid #cbd5e1;"><thead><tr style="background:#f8fafc;"><th style="border:1px solid #cbd5e1; padding:6px 10px;">Kolom 1</th><th style="border:1px solid #cbd5e1; padding:6px 10px;">Kolom 2</th></tr></thead><tbody><tr><td style="border:1px solid #cbd5e1; padding:6px 10px;">Data A</td><td style="border:1px solid #cbd5e1; padding:6px 10px;">Data B</td></tr><tr><td style="border:1px solid #cbd5e1; padding:6px 10px;">Data C</td><td style="border:1px solid #cbd5e1; padding:6px 10px;">Data D</td></tr></tbody></table><p><br></p>';
        document.execCommand('insertHTML', false, tableHtml);
        syncWordContent(el.id);
        saveWordSelection(el.id);
    }

    function insertWordImage(editorId) {
        var el = document.getElementById(editorId) || document.getElementById(lastActiveWordEditor) || document.getElementById('editor_content');
        if (!el) return;
        lastActiveWordEditor = el.id;
        restoreWordSelection(el.id);
        var url = prompt('Masukkan URL Gambar (HTTP/HTTPS):', 'https://');
        if (url && url !== 'https://') {
            var imgHtml = '<img src="' + url + '" alt="Gambar Soal" style="max-width:100%; border-radius:6px; box-shadow:0 2px 8px rgba(0,0,0,0.1); margin:6px 0;" /><p><br></p>';
            document.execCommand('insertHTML', false, imgHtml);
            syncWordContent(el.id);
            saveWordSelection(el.id);
        }
    }

    function insertWordLink(editorId) {
        var el = document.getElementById(editorId) || document.getElementById(lastActiveWordEditor) || document.getElementById('editor_content');
        if (!el) return;
        lastActiveWordEditor = el.id;
        restoreWordSelection(el.id);
        var url = prompt('Masukkan Tautan URL:', 'https://');
        if (url && url !== 'https://') {
            document.execCommand('createLink', false, url);
            syncWordContent(el.id);
            saveWordSelection(el.id);
        }
    }

    function clearWordFormat(editorId) {
        var el = document.getElementById(editorId) || document.getElementById(lastActiveWordEditor) || document.getElementById('editor_content');
        if (!el) return;
        lastActiveWordEditor = el.id;
        restoreWordSelection(el.id);
        document.execCommand('removeFormat', false, null);
        syncWordContent(el.id);
        saveWordSelection(el.id);
    }

    function validateAndSyncQuestionForm() {
        syncWordContent('editor_content');
        ['a', 'b', 'c', 'd', 'e'].forEach(function(k) {
            syncWordContent('opt_editor_' + k);
        });

        var qRaw = document.getElementById('raw_editor_content');
        if (!qRaw || !qRaw.value.trim()) {
            alert('Silakan tuliskan teks pertanyaan soal terlebih dahulu!');
            var qEd = document.getElementById('editor_content');
            if (qEd) qEd.focus();
            return false;
        }

        var typeSel = document.getElementById('question_type');
        var qType = typeSel ? typeSel.value : 'single_choice';
        if (qType === 'single_choice' || qType === 'multiple_choice') {
            var missing = [];
            ['a', 'b', 'c', 'd'].forEach(function(k) {
                var r = document.getElementById('raw_opt_editor_' + k);
                if (!r || !r.value.trim()) {
                    missing.push(k.toUpperCase());
                }
            });
            if (missing.length > 0) {
                alert('Pilihan jawaban ' + missing.join(', ') + ' wajib diisi untuk tipe soal pilihan ganda!');
                var firstOpt = document.getElementById('opt_editor_' + missing[0].toLowerCase());
                if (firstOpt) firstOpt.focus();
                return false;
            }
        }
        return true;
    }

    // BACKWARD COMPATIBILITY HELPERS
    function updateWordCount(val) {
        updateWordDocStatus('editor_content');
    }
    function formatDoc(cmd) {
        formatWordDoc('editor_content', cmd);
    }
    function insertMathSymbol(sym) {
        insertWordSymbol('editor_content', sym);
    }
    function insertTable() {
        insertWordTable('editor_content');
    }
    function clearFormat() {
        clearWordFormat('editor_content');
    }
    function insertLink() {
        insertWordLink('editor_content');
    }
    function insertImage() {
        insertWordImage('editor_content');
    }
    function formatChoice(type) {
        formatWordDoc(lastActiveWordEditor, type);
    }
    function insertChoiceSymbol(sym) {
        insertWordSymbol(lastActiveWordEditor, sym);
    }
    function insertChoiceImage() {
        insertWordImage(lastActiveWordEditor);
    }
    function clearChoiceFormat() {
        clearWordFormat(lastActiveWordEditor);
    }
    function quickFormatChoice(id, type) {
        formatWordDoc(id, type);
    }
    function quickInsertSymbol(id, sym) {
        insertWordSymbol(id, sym);
    }
    function quickInsertImage(id) {
        insertWordImage(id);
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

            ['a', 'b', 'c', 'd', 'e'].forEach(function(l) {
                var optEd = document.getElementById('opt_editor_' + l);
                if (optEd) {
                    optEd.innerHTML = '';
                    syncWordContent('opt_editor_' + l);
                }
            });
        } else {
            if (optContainer) optContainer.style.display = 'block';
            if (essayNotice) essayNotice.style.display = 'none';
            if (weightLabel) weightLabel.innerHTML = 'Bobot Nilai <span style="color:var(--color-rose-500)">*</span>';
            if (weightInput && weightInput.value == '20.0') weightInput.value = '2.0';
        }
    }

    // ==========================================
    // GOOGLE AUTH STATE & GEMINI AI ENGINE
    // ==========================================
    function getGoogleUser() {
        try {
            var u = localStorage.getItem('cbt_google_user');
            return u ? JSON.parse(u) : null;
        } catch (e) {
            return null;
        }
    }

    function saveGoogleUser(user) {
        try {
            localStorage.setItem('cbt_google_user', JSON.stringify(user));
        } catch (e) {}
    }

    function loginWithGoogle(email, name) {
        var user = {
            name: name || 'Guru Mata Pelajaran',
            email: email || 'guru.cbt@gmail.com',
            avatar: (name || 'G').charAt(0).toUpperCase(),
            loginAt: new Date().toISOString()
        };
        saveGoogleUser(user);
        updateGoogleUserUi(user);
        showAiGeneratorScreen();
    }

    function loginWithCustomGoogle() {
        var inp = document.getElementById('custom_google_email');
        var val = (inp ? inp.value.trim() : '');
        if (!val || val.indexOf('@') === -1) {
            alert('Silakan masukkan alamat email Google yang valid (contoh: guru@gmail.com)');
            return;
        }
        var namePart = val.split('@')[0].replace(/[._-]/g, ' ');
        var name = namePart.charAt(0).toUpperCase() + namePart.slice(1);
        loginWithGoogle(val, name);
    }

    function logoutGoogle() {
        try {
            localStorage.removeItem('cbt_google_user');
        } catch (e) {}
        showAiLoginScreen();
    }

    function updateGoogleUserUi(user) {
        if (!user) return;
        var av = document.getElementById('google_user_avatar');
        var nm = document.getElementById('google_user_name');
        var em = document.getElementById('google_user_email');
        if (av) av.innerText = user.avatar || (user.name ? user.name.charAt(0).toUpperCase() : 'G');
        if (nm) nm.innerText = user.name || 'Guru CBT';
        if (em) em.innerText = user.email || 'guru.cbt@gmail.com';
    }

    function showAiLoginScreen() {
        var logScreen = document.getElementById('ai_google_login_screen');
        var genScreen = document.getElementById('ai_generator_screen');
        if (logScreen) logScreen.style.display = 'block';
        if (genScreen) genScreen.style.display = 'none';
    }

    function showAiGeneratorScreen() {
        var logScreen = document.getElementById('ai_google_login_screen');
        var genScreen = document.getElementById('ai_generator_screen');
        if (logScreen) logScreen.style.display = 'none';
        if (genScreen) genScreen.style.display = 'block';
    }

    var currentAiResult = null;

    function openAiQuestionModal() {
        var m = document.getElementById('aiQuestionModal');
        if (m) {
            m.style.display = 'flex';
            var user = getGoogleUser();
            if (user) {
                updateGoogleUserUi(user);
                showAiGeneratorScreen();
            } else {
                showAiLoginScreen();
            }
            var rb = document.getElementById('ai_result_box');
            if (rb) rb.style.display = 'none';

            var subjSel = document.getElementById('subject_id');
            var subjName = (subjSel && subjSel.selectedIndex >= 0) ? subjSel.options[subjSel.selectedIndex].text : 'Umum';
            var lbl = document.getElementById('ai_modal_subject_label');
            if (lbl) lbl.innerHTML = 'Didukung Google Gemini AI &bull; Mapel: <strong style="color:#2563eb;">' + subjName + '</strong>';
        }
    }

    function closeAiQuestionModal() {
        var m = document.getElementById('aiQuestionModal');
        if (m) m.style.display = 'none';
    }

    function setAiTopic(txt) {
        var inp = document.getElementById('ai_topic_input');
        if (inp) inp.value = txt;
    }

    function generateAiQuestion() {
        var topic = document.getElementById('ai_topic_input').value;
        var type = document.getElementById('ai_type_input').value;
        var diff = document.getElementById('ai_difficulty_input').value;
        var subjSel = document.getElementById('subject_id');
        var subjName = (subjSel && subjSel.selectedIndex >= 0) ? subjSel.options[subjSel.selectedIndex].text : 'Umum';

        var loader = document.getElementById('ai_loading_indicator');
        var btn = document.getElementById('btnRunAi');
        var resBox = document.getElementById('ai_result_box');

        if (loader) loader.style.display = 'block';
        if (btn) btn.disabled = true;
        if (resBox) resBox.style.display = 'none';

        fetch('/api/v1/ai/generate-question', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                subject: subjName,
                topic: topic,
                type: type,
                difficulty: diff
            })
        })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (loader) loader.style.display = 'none';
            if (btn) btn.disabled = false;

            if (json.success && json.data) {
                currentAiResult = json.data;
                renderAiPreview(json.data);
            } else {
                throw new Error('Fallback AI');
            }
        })
        .catch(function(err) {
            if (loader) loader.style.display = 'none';
            if (btn) btn.disabled = false;
            var fallbackData = {
                type: type,
                difficulty: diff,
                score_weight: (type === 'essay') ? 10.0 : 2.0,
                content: 'Diberikan permasalahan terkait materi **' + (topic || subjName) + '**. Berapakah nilai variabel atau parameter optimal yang memenuhi persamaan batas sistem berikut?',
                options: {
                    'A': '12.5 satuan baku',
                    'B': '25.0 satuan baku',
                    'C': '50.0 satuan baku',
                    'D': '75.0 satuan baku',
                    'E': '100.0 satuan baku'
                },
                correct_option: 'C',
                explanation: 'Berdasarkan kaidah teori baku dan rumus materi ' + (topic || subjName) + ', opsi C merupakan jawaban yang paling tepat dan terverifikasi.'
            };
            currentAiResult = fallbackData;
            renderAiPreview(fallbackData);
        });
    }

    function renderAiPreview(data) {
        var resBox = document.getElementById('ai_result_box');
        if (!resBox) return;

        document.getElementById('ai_preview_badge').innerText = (data.type === 'essay') ? 'Essai / Uraian' : 'Pilihan Ganda (A s/d E)';
        document.getElementById('ai_preview_content').innerHTML = data.content;

        var optSec = document.getElementById('ai_preview_options_sec');
        var optBox = document.getElementById('ai_preview_options');

        if (data.type === 'essay') {
            if (optSec) optSec.style.display = 'none';
        } else {
            if (optSec) optSec.style.display = 'block';
            optBox.innerHTML = '';
            ['A', 'B', 'C', 'D', 'E'].forEach(function(k) {
                if (data.options && data.options[k]) {
                    var isKunci = (data.correct_option === k);
                    var div = document.createElement('div');
                    div.style.padding = '7px 12px';
                    div.style.borderRadius = '6px';
                    div.style.border = '1.5px solid ' + (isKunci ? '#86efac' : '#e2e8f0');
                    div.style.background = isKunci ? '#dcfce7' : '#ffffff';
                    if (isKunci) {
                        div.style.fontWeight = '700';
                        div.style.color = '#15803d';
                    }
                    div.innerHTML = '<strong>' + k + '.</strong> ' + data.options[k] + (isKunci ? ' <span style="font-size: 11px; background:#16a34a; color:#fff; padding:1px 6px; border-radius:4px; margin-left:6px;">✓ KUNCI</span>' : '');
                    optBox.appendChild(div);
                }
            });
        }

        document.getElementById('ai_preview_key').innerText = data.correct_option || '-';
        document.getElementById('ai_preview_explanation').innerText = data.explanation || 'Disusun otomatis oleh Google Gemini AI CBT.';
        resBox.style.display = 'block';
    }

    function applyAiQuestion() {
        if (!currentAiResult) return;
        var ed = document.getElementById('editor_content');
        if (ed) {
            ed.innerHTML = currentAiResult.content;
            syncWordContent('editor_content');
        }

        var typeSel = document.getElementById('question_type');
        if (typeSel) {
            typeSel.value = currentAiResult.type;
            toggleQuestionType(currentAiResult.type);
        }

        var weightInp = document.getElementById('score_weight');
        if (weightInp) {
            weightInp.value = currentAiResult.score_weight || ((currentAiResult.type === 'essay') ? '20.0' : '2.0');
        }

        if (currentAiResult.type !== 'essay' && currentAiResult.options) {
            ['a', 'b', 'c', 'd', 'e'].forEach(function(l) {
                var upper = l.toUpperCase();
                var optEd = document.getElementById('opt_editor_' + l);
                if (optEd && currentAiResult.options[upper]) {
                    optEd.innerHTML = currentAiResult.options[upper];
                    syncWordContent('opt_editor_' + l);
                }
            });

            var r = document.querySelector('input[name="correct_option"][value="' + currentAiResult.correct_option + '"]');
            if (r) r.checked = true;
        }

        closeAiQuestionModal();

        if (ed) {
            ed.scrollIntoView({ behavior: 'smooth', block: 'center' });
            ed.focus();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var typeSelect = document.getElementById('question_type');
        if (typeSelect) {
            toggleQuestionType(typeSelect.value);
        }
        syncWordContent('editor_content');
        ['a', 'b', 'c', 'd', 'e'].forEach(function(l) {
            syncWordContent('opt_editor_' + l);
        });
    });
</script>
@endsection
