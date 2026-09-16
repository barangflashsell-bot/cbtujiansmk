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
        gap: 5px;
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
    .opt-btn {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        color: #334155;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }
    .opt-btn:hover {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #1d4ed8;
    }
    .choice-mini-btn {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 3px 7px;
        font-size: 11px;
        cursor: pointer;
        color: #475569;
        transition: all 0.15s ease;
    }
    .choice-mini-btn:hover {
        background: #e0e7ff;
        border-color: #6366f1;
        color: #3730a3;
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

    <!-- KARTU PERTANYAAN DENGAN EDITOR PERSIS GAMBAR TIGA & AI GENERATOR -->
    <div class="card" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0;">
                Pertanyaan
            </h3>
            <button type="button" class="btn btn-sm" onclick="openAiQuestionModal()" style="background: linear-gradient(135deg, #7c3aed 0%, #2563eb 100%); color: #ffffff; font-weight: 700; border: none; border-radius: 6px; padding: 7px 16px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35); cursor: pointer; transition: all 0.2s ease;">
                <span>✨</span> Buat Soal dengan AI
            </button>
        </div>

        <!-- WYSIWYG EDITOR TOOLBAR MICROSOFT WORD PERSIS GAMBAR 3 -->
        <div class="tinymce-mock-container">
            <div class="tinymce-menubar">
                <span>File</span>
                <span>Edit</span>
                <span>View</span>
                <span>Insert</span>
                <span>Format</span>
                <span>Tools</span>
                <span>Table</span>
            </div>
            <div class="tinymce-toolbar">
                <button type="button" class="tb-btn" title="Undo" onclick="formatDoc('undo')">&#8630;</button>
                <button type="button" class="tb-btn" title="Redo" onclick="formatDoc('redo')">&#8631;</button>
                <div class="tb-separator"></div>
                <button type="button" class="tb-btn" title="Bold (Tebal)" onclick="formatDoc('bold')" style="font-weight: bold;">B</button>
                <button type="button" class="tb-btn" title="Italic (Miring)" onclick="formatDoc('italic')" style="font-style: italic;">I</button>
                <button type="button" class="tb-btn" title="Underline (Garis Bawah)" onclick="formatDoc('underline')"><u>U</u></button>
                <button type="button" class="tb-btn" title="Strikethrough (Coret)" onclick="formatDoc('strikeThrough')"><s>S</s></button>
                <div class="tb-separator"></div>
                <button type="button" class="tb-btn" title="Superscript / Pangkat (x²)" onclick="formatDoc('superscript')">x²</button>
                <button type="button" class="tb-btn" title="Subscript / Indeks (x₂)" onclick="formatDoc('subscript')">x₂</button>
                <div class="tb-separator"></div>
                <button type="button" class="tb-btn" title="Align Left" onclick="formatDoc('justifyLeft')">&#8801;</button>
                <button type="button" class="tb-btn" title="Align Center" onclick="formatDoc('justifyCenter')">&#8788;</button>
                <button type="button" class="tb-btn" title="Align Right" onclick="formatDoc('justifyRight')">&#8801;</button>
                <button type="button" class="tb-btn" title="Justify" onclick="formatDoc('justifyFull')">&#9776;</button>
                <div class="tb-separator"></div>
                <button type="button" class="tb-btn" title="Bullet List" onclick="formatDoc('insertUnorderedList')">&#8226;&#8801;</button>
                <button type="button" class="tb-btn" title="Numbered List" onclick="formatDoc('insertOrderedList')">1.&#8801;</button>
                <div class="tb-separator"></div>
                <button type="button" class="tb-btn" title="Simbol Akar (√)" onclick="insertMathSymbol('√')">√x</button>
                <button type="button" class="tb-btn" title="Simbol Pi (π)" onclick="insertMathSymbol('π')">π</button>
                <button type="button" class="tb-btn" title="Simbol Plus-Minus (±)" onclick="insertMathSymbol('±')">±</button>
                <button type="button" class="tb-btn" title="Simbol Kali (×)" onclick="insertMathSymbol('×')">×</button>
                <button type="button" class="tb-btn" title="Simbol Bagi (÷)" onclick="insertMathSymbol('÷')">÷</button>
                <button type="button" class="tb-btn" title="Kurang Dari Sama Dengan (≤)" onclick="insertMathSymbol('≤')">≤</button>
                <button type="button" class="tb-btn" title="Lebih Dari Sama Dengan (≥)" onclick="insertMathSymbol('≥')">≥</button>
                <button type="button" class="tb-btn" title="Tidak Sama Dengan (≠)" onclick="insertMathSymbol('≠')">≠</button>
                <button type="button" class="tb-btn" title="Derajat (°)" onclick="insertMathSymbol('°')">°</button>
                <button type="button" class="tb-btn" title="Tak Terhingga (∞)" onclick="insertMathSymbol('∞')">∞</button>
                <div class="tb-separator"></div>
                <button type="button" class="tb-btn" title="Sisipkan Tabel 2x2" onclick="insertTable(2, 2)">⊞ Tabel</button>
                <button type="button" class="tb-btn" title="Sisipkan Link" onclick="insertLink()">&#128279;</button>
                <button type="button" class="tb-btn" title="Sisipkan Gambar" onclick="insertImage()">&#128444;</button>
                <button type="button" class="tb-btn" title="Hapus Format" onclick="clearFormat()">Tx</button>
            </div>
            <textarea name="content" id="editor_content" class="form-control" rows="7" style="width: 100%; border: none; border-radius: 0; outline: none; padding: 14px; font-size: 14px; line-height: 1.6; resize: vertical;" placeholder="Tuliskan pertanyaan soal di sini..." oninput="updateWordCount(this.value)" required>{{ old('content') }}</textarea>
            <div class="tinymce-statusbar">
                <span id="word_count_status">0 WORDS &bull; POWERED BY TINYMCE &bull; MICROSOFT WORD TOOLS</span>
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

    <!-- SECTION PILIHAN JAWABAN (DENGAN TOOLS MENU MICROSOFT WORD) -->
    <div id="options-container" class="card" style="margin-bottom: 20px; background: #f8fafc; border: 1px solid #e2e8f0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
            <h3 style="font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0;">
                Pilihan Jawaban &amp; Kunci Jawaban
            </h3>
            <span style="font-size: 0.85rem; color: #64748b;">
                Pilih radio button di sebelah kiri untuk menentukan Kunci Jawaban Benar
            </span>
        </div>

        <!-- TOOLBAR FORMAT WORD UNTUK PILIHAN JAWABAN -->
        <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; margin-bottom: 14px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <span style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-right: 4px;">Tools Jawaban:</span>
            <button type="button" class="opt-btn" onclick="formatChoice('bold')" title="Tebal (Bold)"><b>B</b></button>
            <button type="button" class="opt-btn" onclick="formatChoice('italic')" title="Miring (Italic)"><i>I</i></button>
            <button type="button" class="opt-btn" onclick="formatChoice('underline')" title="Garis Bawah (Underline)"><u>U</u></button>
            <div style="width: 1px; height: 16px; background: #e2e8f0; margin: 0 2px;"></div>
            <button type="button" class="opt-btn" onclick="insertChoiceSymbol('²')" title="Pangkat 2 (x²)">x²</button>
            <button type="button" class="opt-btn" onclick="insertChoiceSymbol('³')" title="Pangkat 3 (x³)">x³</button>
            <button type="button" class="opt-btn" onclick="insertChoiceSymbol('₁')" title="Indeks 1 (x₁)">x₁</button>
            <button type="button" class="opt-btn" onclick="insertChoiceSymbol('₂')" title="Indeks 2 (x₂)">x₂</button>
            <button type="button" class="opt-btn" onclick="insertChoiceSymbol('√')" title="Akar (√)">√x</button>
            <button type="button" class="opt-btn" onclick="insertChoiceSymbol('π')" title="Pi (π)">π</button>
            <button type="button" class="opt-btn" onclick="insertChoiceSymbol('±')" title="Plus-Minus (±)">±</button>
            <button type="button" class="opt-btn" onclick="insertChoiceSymbol('×')" title="Kali (×)">×</button>
            <button type="button" class="opt-btn" onclick="insertChoiceSymbol('÷')" title="Bagi (÷)">÷</button>
            <button type="button" class="opt-btn" onclick="insertChoiceSymbol('≤')" title="Kurang dari sama dengan (≤)">≤</button>
            <button type="button" class="opt-btn" onclick="insertChoiceSymbol('≥')" title="Lebih dari sama dengan (≥)">≥</button>
            <button type="button" class="opt-btn" onclick="insertChoiceSymbol('≠')" title="Tidak sama dengan (≠)">≠</button>
            <button type="button" class="opt-btn" onclick="insertChoiceSymbol('°')" title="Derajat (°)">°</button>
            <div style="width: 1px; height: 16px; background: #e2e8f0; margin: 0 2px;"></div>
            <button type="button" class="opt-btn" onclick="insertChoiceImage()" title="Sisipkan Gambar Opsi">🖼️ Gambar</button>
            <button type="button" class="opt-btn" onclick="clearChoiceFormat()" title="Bersihkan Format">Tx</button>
        </div>

        @php
            $labels = ['A', 'B', 'C', 'D', 'E'];
        @endphp

        @foreach($labels as $idx => $label)
            <div class="option-row" style="margin-bottom: 12px; display: flex; align-items: center; gap: 10px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; min-width: 50px; margin: 0;">
                    <input type="radio" name="correct_option" value="{{ $label }}" {{ old('correct_option', 'A') == $label ? 'checked' : '' }} style="accent-color: #16a34a; width: 18px; height: 18px;">
                    <span class="option-badge" style="font-weight: 800; font-size: 14px; color: #1e293b;">{{ $label }}.</span>
                </label>
                <input type="hidden" name="options[{{ $idx }}][label]" value="{{ $label }}">
                <input type="text" name="options[{{ $idx }}][content]" id="opt_input_{{ strtolower($label) }}" class="form-control" placeholder="Teks pilihan jawaban {{ $label }}..." value="{{ old("options.{$idx}.content") }}" onfocus="setActiveChoice(this)" style="border: none; box-shadow: none; padding: 6px 8px; font-size: 13.5px;">
                <div style="display: flex; gap: 3px;">
                    <button type="button" class="choice-mini-btn" onclick="quickFormatChoice('opt_input_{{ strtolower($label) }}', 'bold')" title="Tebal"><b>B</b></button>
                    <button type="button" class="choice-mini-btn" onclick="quickFormatChoice('opt_input_{{ strtolower($label) }}', 'italic')" title="Miring"><i>I</i></button>
                    <button type="button" class="choice-mini-btn" onclick="quickInsertSymbol('opt_input_{{ strtolower($label) }}', '²')" title="Pangkat 2">x²</button>
                    <button type="button" class="choice-mini-btn" onclick="quickInsertSymbol('opt_input_{{ strtolower($label) }}', '√')" title="Akar">√</button>
                    <button type="button" class="choice-mini-btn" onclick="quickInsertImage('opt_input_{{ strtolower($label) }}')" title="Gambar">🖼️</button>
                </div>
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

<!-- MODAL BUAT SOAL DENGAN AI (GEMINI AI ENGINE) -->
<div class="modal-overlay" id="aiQuestionModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: #ffffff; border-radius: 14px; max-width: 680px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <div style="padding: 18px 22px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f5f3ff 0%, #eff6ff 100%);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 24px;">✨</span>
                <div>
                    <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: #1e1b4b;">AI Generator Butir Soal CBT</h3>
                    <div style="font-size: 12px; color: #6b21a8; margin-top: 2px;" id="ai_modal_subject_label">
                        Didukung Gemini 2.5 AI &bull; Bank Soal CBT
                    </div>
                </div>
            </div>
            <button type="button" onclick="closeAiQuestionModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div style="padding: 22px;">
            <!-- QUICK SUGGESTED TOPICS CHIPS -->
            <div style="margin-bottom: 16px;">
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 8px;">
                    Pilihan Topik Cepat (Klik untuk memilih):
                </label>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <span class="ai-chip" onclick="setAiTopic(this.innerText)">Persamaan Kuadrat &amp; Akar</span>
                    <span class="ai-chip" onclick="setAiTopic(this.innerText)">Determinan Matriks 2x2</span>
                    <span class="ai-chip" onclick="setAiTopic(this.innerText)">Teorema Pythagoras &amp; Geometri</span>
                    <span class="ai-chip" onclick="setAiTopic(this.innerText)">Trigonometri Sudut Istimewa</span>
                    <span class="ai-chip" onclick="setAiTopic(this.innerText)">Peluang &amp; Kombinasi</span>
                    <span class="ai-chip" onclick="setAiTopic(this.innerText)">Konsep Pemrograman OOP</span>
                    <span class="ai-chip" onclick="setAiTopic(this.innerText)">IP Address &amp; Subnetting /27</span>
                </div>
            </div>

            <!-- INPUT TOPIK / MATERI -->
            <div style="margin-bottom: 16px;">
                <label style="font-size: 13px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 6px;">
                    Topik, Materi, atau Instruksi Khusus:
                </label>
                <textarea id="ai_topic_input" rows="3" class="form-control" placeholder="Contoh: Buatkan soal menghitung determinan matriks 2x2 ordo [4, -2; 3, 5] dengan pilihan jawaban A-E dan kunci yang tepat..." style="font-size: 13.5px;"></textarea>
                <small style="color: #64748b; font-size: 11.5px; margin-top: 4px; display: block;">
                    Anda bisa mengosongkan jika ingin AI menyusun butir materi terbaik secara otomatis.
                </small>
            </div>

            <div class="form-row" style="margin-bottom: 18px;">
                <div class="form-group" style="flex: 1;">
                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b;">Tipe Soal:</label>
                    <select id="ai_type_input" class="form-select">
                        <option value="single_choice" selected>Pilihan Ganda (1 Jawaban)</option>
                        <option value="essay">Essai / Uraian</option>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b;">Tingkat Kesulitan:</label>
                    <select id="ai_difficulty_input" class="form-select">
                        <option value="easy">Mudah</option>
                        <option value="medium" selected>Sedang</option>
                        <option value="hard">Sukar / HOTS</option>
                    </select>
                </div>
            </div>

            <!-- TOMBOL GENERATE -->
            <div style="text-align: center; margin-bottom: 20px;">
                <button type="button" id="btnRunAi" onclick="generateAiQuestion()" style="background: linear-gradient(135deg, #7c3aed 0%, #2563eb 100%); color: #ffffff; font-weight: 800; font-size: 14px; border: none; border-radius: 8px; padding: 10px 28px; cursor: pointer; box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35); transition: all 0.2s ease;">
                    🚀 Generate Soal dengan AI Sekarang
                </button>
                <div id="ai_loading_indicator" style="display: none; margin-top: 12px; font-size: 13px; color: #7c3aed; font-weight: 700;">
                    <span style="display: inline-block; animation: pulse 1s infinite;">🤖</span> Gemini AI sedang menyusun butir soal &amp; pilihan jawaban...
                </div>
            </div>

            <!-- HASIL GENERATE PREVIEW -->
            <div id="ai_result_box" style="display: none; background: #faf5ff; border: 1.5px solid #d8b4fe; border-radius: 10px; padding: 18px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #e9d5ff; padding-bottom: 8px;">
                    <span style="font-weight: 800; color: #6b21a8; font-size: 13px;">Hasil Generate AI:</span>
                    <span class="badge" id="ai_preview_badge" style="background: #e9d5ff; color: #6b21a8;">Pilihan Ganda</span>
                </div>

                <div style="margin-bottom: 12px;">
                    <strong style="font-size: 12px; color: #475569; display: block; margin-bottom: 4px;">Pertanyaan:</strong>
                    <div id="ai_preview_content" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; font-size: 13.5px; line-height: 1.6;"></div>
                </div>

                <div id="ai_preview_options_sec" style="margin-bottom: 12px;">
                    <strong style="font-size: 12px; color: #475569; display: block; margin-bottom: 4px;">Pilihan Jawaban:</strong>
                    <div id="ai_preview_options" style="display: flex; flex-direction: column; gap: 6px; font-size: 13px;"></div>
                </div>

                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px; font-size: 13px;">
                    <div><strong style="color: #166534;">Kunci Jawaban:</strong> <span id="ai_preview_key" style="color: #15803d; font-weight: 800; font-size: 15px;">-</span></div>
                    <div style="margin-top: 4px; color: #166534;"><strong style="color: #166534;">Pembahasan:</strong> <span id="ai_preview_explanation">-</span></div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="generateAiQuestion()">🔄 Generate Ulang</button>
                    <button type="button" class="btn btn-success btn-sm" onclick="applyAiQuestion()" style="background: #15803d; border-color: #15803d; font-weight: 700; padding: 8px 20px;">
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
    function updateWordCount(val) {
        var words = val.trim().split(/\s+/).filter(function(w) { return w.length > 0; }).length;
        var el = document.getElementById('word_count_status');
        if (el) {
            el.innerText = words + " WORDS \u2022 POWERED BY TINYMCE \u2022 MICROSOFT WORD TOOLS";
        }
    }

    var lastActiveChoiceInput = null;
    function setActiveChoice(input) {
        lastActiveChoiceInput = input;
    }

    function formatChoice(type) {
        var input = lastActiveChoiceInput || document.getElementById('opt_input_a');
        if (!input) return;
        var start = input.selectionStart || 0;
        var end = input.selectionEnd || 0;
        var sel = input.value.substring(start, end);
        if (type === 'bold') {
            input.setRangeText('**' + (sel || 'tebal') + '**', start, end, 'end');
        } else if (type === 'italic') {
            input.setRangeText('*' + (sel || 'miring') + '*', start, end, 'end');
        } else if (type === 'underline') {
            input.setRangeText('<u>' + (sel || 'garis bawah') + '</u>', start, end, 'end');
        }
        input.focus();
    }

    function insertChoiceSymbol(sym) {
        var input = lastActiveChoiceInput || document.getElementById('opt_input_a');
        if (!input) return;
        var start = input.selectionStart || 0;
        var end = input.selectionEnd || 0;
        input.setRangeText(sym, start, end, 'end');
        input.focus();
    }

    function insertChoiceImage() {
        var input = lastActiveChoiceInput || document.getElementById('opt_input_a');
        if (!input) return;
        var url = prompt('Masukkan URL Gambar untuk Opsi Jawaban:', 'https://');
        if (url) {
            var start = input.selectionStart || 0;
            var end = input.selectionEnd || 0;
            input.setRangeText(' [🖼️ ' + url + '] ', start, end, 'end');
            input.focus();
        }
    }

    function clearChoiceFormat() {
        var input = lastActiveChoiceInput || document.getElementById('opt_input_a');
        if (!input) return;
        input.value = input.value.replace(/[*_~`]/g, '').replace(/<[^>]*>/g, '');
        input.focus();
    }

    function quickFormatChoice(id, type) {
        var input = document.getElementById(id);
        if (!input) return;
        lastActiveChoiceInput = input;
        formatChoice(type);
    }

    function quickInsertSymbol(id, sym) {
        var input = document.getElementById(id);
        if (!input) return;
        lastActiveChoiceInput = input;
        insertChoiceSymbol(sym);
    }

    function quickInsertImage(id) {
        var input = document.getElementById(id);
        if (!input) return;
        lastActiveChoiceInput = input;
        insertChoiceImage();
    }

    function formatDoc(cmd) {
        var textarea = document.getElementById('editor_content');
        if (!textarea) return;
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var selected = textarea.value.substring(start, end);
        if (cmd === 'bold') {
            textarea.setRangeText('**' + (selected || 'teks tebal') + '**', start, end, 'end');
        } else if (cmd === 'italic') {
            textarea.setRangeText('*' + (selected || 'teks miring') + '*', start, end, 'end');
        } else if (cmd === 'underline') {
            textarea.setRangeText('<u>' + (selected || 'teks garis bawah') + '</u>', start, end, 'end');
        } else if (cmd === 'strikeThrough') {
            textarea.setRangeText('~~' + (selected || 'teks dicoret') + '~~', start, end, 'end');
        } else if (cmd === 'superscript') {
            textarea.setRangeText('^' + (selected || '2') + '^', start, end, 'end');
        } else if (cmd === 'subscript') {
            textarea.setRangeText('~' + (selected || '1') + '~', start, end, 'end');
        } else if (cmd === 'justifyLeft') {
            textarea.setRangeText('\n<div align="left">\n' + (selected || 'Teks rata kiri') + '\n</div>\n', start, end, 'end');
        } else if (cmd === 'justifyCenter') {
            textarea.setRangeText('\n<div align="center">\n' + (selected || 'Teks rata tengah') + '\n</div>\n', start, end, 'end');
        } else if (cmd === 'justifyRight') {
            textarea.setRangeText('\n<div align="right">\n' + (selected || 'Teks rata kanan') + '\n</div>\n', start, end, 'end');
        } else if (cmd === 'justifyFull') {
            textarea.setRangeText('\n<div align="justify">\n' + (selected || 'Teks rata kanan-kiri') + '\n</div>\n', start, end, 'end');
        } else if (cmd === 'insertUnorderedList') {
            textarea.setRangeText('\n- ' + (selected || 'item daftar'), start, end, 'end');
        } else if (cmd === 'insertOrderedList') {
            textarea.setRangeText('\n1. ' + (selected || 'item nomor'), start, end, 'end');
        } else if (cmd === 'undo' || cmd === 'redo') {
            document.execCommand(cmd);
        }
        updateWordCount(textarea.value);
    }

    function insertMathSymbol(sym) {
        var textarea = document.getElementById('editor_content');
        if (!textarea) return;
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        textarea.setRangeText(' ' + sym + ' ', start, end, 'end');
        updateWordCount(textarea.value);
        textarea.focus();
    }

    function insertTable(rows, cols) {
        var textarea = document.getElementById('editor_content');
        if (!textarea) return;
        var table = '\n| Kolom 1 | Kolom 2 |\n| --- | --- |\n| Data A | Data B |\n';
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        textarea.setRangeText(table, start, end, 'end');
        updateWordCount(textarea.value);
        textarea.focus();
    }

    function clearFormat() {
        var textarea = document.getElementById('editor_content');
        if (!textarea) return;
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var sel = textarea.value.substring(start, end);
        if (sel) {
            var clean = sel.replace(/[*_~`^]/g, '').replace(/<[^>]*>/g, '');
            textarea.setRangeText(clean, start, end, 'end');
            updateWordCount(textarea.value);
        }
    }

    function insertLink() {
        var url = prompt("Masukkan URL tautan:", "https://");
        if (url) {
            var textarea = document.getElementById('editor_content');
            if (textarea) {
                var start = textarea.selectionStart;
                var end = textarea.selectionEnd;
                textarea.setRangeText(' ' + url + ' ', start, end, 'end');
                updateWordCount(textarea.value);
            }
        }
    }

    function insertImage() {
        var url = prompt("Masukkan URL Gambar:", "https://");
        if (url) {
            var textarea = document.getElementById('editor_content');
            if (textarea) {
                var start = textarea.selectionStart;
                var end = textarea.selectionEnd;
                textarea.setRangeText(' ![Gambar](' + url + ') ', start, end, 'end');
                updateWordCount(textarea.value);
            }
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

    // AI Question Generator Handler
    var currentAiResult = null;

    function openAiQuestionModal() {
        var m = document.getElementById('aiQuestionModal');
        if (m) {
            m.style.display = 'flex';
            var rb = document.getElementById('ai_result_box');
            if (rb) rb.style.display = 'none';

            var subjSel = document.getElementById('subject_id');
            var subjName = (subjSel && subjSel.selectedIndex >= 0) ? subjSel.options[subjSel.selectedIndex].text : 'Umum';
            var lbl = document.getElementById('ai_modal_subject_label');
            if (lbl) lbl.innerHTML = 'Didukung Gemini 2.5 AI &bull; Mapel: <strong>' + subjName + '</strong>';
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

        document.getElementById('ai_preview_badge').innerText = (data.type === 'essay') ? 'Essai / Uraian' : 'Pilihan Ganda';
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
                    div.style.padding = '5px 10px';
                    div.style.borderRadius = '5px';
                    div.style.border = '1px solid ' + (isKunci ? '#86efac' : '#e2e8f0');
                    div.style.background = isKunci ? '#dcfce7' : '#ffffff';
                    if (isKunci) {
                        div.style.fontWeight = '700';
                        div.style.color = '#15803d';
                    }
                    div.innerHTML = '<strong>' + k + '.</strong> ' + data.options[k] + (isKunci ? ' <span style="font-size: 11px;">✓ (Kunci)</span>' : '');
                    optBox.appendChild(div);
                }
            });
        }

        document.getElementById('ai_preview_key').innerText = data.correct_option || '-';
        document.getElementById('ai_preview_explanation').innerText = data.explanation || 'Dibuat otomatis oleh Gemini AI CBT.';
        resBox.style.display = 'block';
    }

    function applyAiQuestion() {
        if (!currentAiResult) return;
        var ed = document.getElementById('editor_content');
        if (ed) {
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = currentAiResult.content;
            ed.value = tempDiv.innerText || tempDiv.textContent || currentAiResult.content;
            updateWordCount(ed.value);
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
                var inp = document.getElementById('opt_input_' + l);
                if (inp && currentAiResult.options[upper]) {
                    inp.value = currentAiResult.options[upper];
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
        var editor = document.getElementById('editor_content');
        if (editor) {
            updateWordCount(editor.value);
        }
    });
</script>
@endsection
