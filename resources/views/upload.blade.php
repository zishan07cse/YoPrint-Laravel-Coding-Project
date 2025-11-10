<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #e5e7eb;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .browser-frame {
            width: 100%;
            max-width: 1000px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.19), 0 6px 6px rgba(0, 0, 0, 0.23);
        }

        .drag-drop-area {
            border: 2px dashed #ccc;
            transition: all 0.2s;
            cursor: pointer;
            padding: 1rem;
            text-align: center;
        }

        .drag-drop-area.hover {
            border-color: #2563eb;
            background-color: #eff6ff;
        }

        #uploadsTable th,
        #uploadsTable td {
            padding: 12px 16px;
        }

        #uploadsTable th {
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
    </style>
</head>

<body>
    <div class="browser-frame bg-white rounded-xl overflow-hidden m-4 md:m-8">
        <div class="p-6 md:p-8">
            <h1 class="text-2xl font-bold mb-4">File Uploader</h1>

            <div id="message-box" class="mt-4 p-3 rounded text-sm hidden transition-all duration-300"></div>

            <!-- Drag & Drop -->
            <form id="uploadForm" enctype="multipart/form-data">
                <!-- Drag and Drop/Select File Area -->
                <label for="fileInput" id="dropArea" class="drag-drop-area flex justify-between items-center p-6 mb-6 rounded-lg bg-gray-50">
                    <span id="dropText" class="text-gray-500 text-lg font-medium">
                        Select file / Drag and drop
                    </span>
                    <input type="file" name="file" id="fileInput" class="hidden" required />
                    <button type="submit" id="uploadButton"
                        class="px-5 py-2 rounded-lg font-semibold shadow-md 
                               bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-200">
                        Upload File
                    </button>
                </label>
            </form>

            <h3 class="text-xl font-semibold mb-4 border-b pb-2">Upload Status</h3>
            <div class="overflow-x-auto rounded-lg shadow-lg">
                <table id="uploadsTable" class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-800 text-white">
                            <th class="rounded-tl-lg">Time</th>
                            <th>File Name</th>
                            <th class="rounded-tr-lg">Status</th>
                        </tr>
                    </thead>
                    <!-- <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($uploads as $upload)
                        <tr data-id="{{ $upload->id }}">
                            <td>{{ $upload->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $upload->file_name }}</td>
                            <td class="{{ $upload->status === 'completed' ? 'text-green-600' : ($upload->status === 'failed' ? 'text-red-600' : 'text-yellow-600') }}">
                                {{ $upload->status }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody> -->
                    <tbody class="bg-white divide-y divide-gray-200">
                        <!-- JS will populate rows -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

        const uploadsTableBody = document.querySelector('#uploadsTable tbody');
        const uploadForm = document.getElementById('uploadForm');
        const uploadButton = document.getElementById('uploadButton');
        const fileInput = document.getElementById('fileInput');
        const messageBox = document.getElementById('message-box');
        const dropArea = document.getElementById('dropArea');
        const dropText = document.getElementById('dropText');

        function showMessage(type, text) {
            messageBox.textContent = text;
            messageBox.className = 'p-3 mb-4 rounded text-sm transition-all duration-300';
            messageBox.classList.remove('hidden');
            if (type === 'success') messageBox.classList.add('bg-green-100', 'text-green-800');
            else if (type === 'error') messageBox.classList.add('bg-red-100', 'text-red-800');
            else messageBox.classList.add('bg-blue-100', 'text-blue-800');
            setTimeout(() => messageBox.classList.add('hidden'), 5000);
        }

        async function fetchUploads() {
            try {
                const res = await axios.get('{{ route("uploads.json") }}');
                const uploads = res.data.data || [];

                if (!uploads.length) {
                    uploadsTableBody.innerHTML = `<tr><td colspan="3" class="text-center text-gray-500 py-4">No uploads yet</td></tr>`;
                    return;
                }

                uploadsTableBody.innerHTML = uploads.map(u => `
                    <tr data-id="${u.id}" class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-4 py-2">${new Date(u.created_at).toLocaleString()}</td>
                        <td class="px-4 py-2"><a href="/storage/uploads/${u.file_name}" target="_blank" class="text-blue-600">${u.file_name}</a></td>
                        <td class="px-4 py-2 font-medium capitalize ${u.status==='completed'?'text-green-600':u.status==='failed'?'text-red-600':'text-yellow-600'}">${u.status}</td>
                    </tr>
                `).join('');
            } catch (err) {
                console.error("Failed to fetch uploads", err);
            }
        }

        // Auto-refresh every 5s
        setInterval(fetchUploads, 5000);

        uploadForm.addEventListener('submit', async e => {
            e.preventDefault();
            uploadButton.disabled = true;
            uploadButton.textContent = 'Uploading...';

            const f = fileInput.files[0];
            if (!f) {
                showMessage('error', 'Select a file');
                resetButton();
                return;
            }

            const fd = new FormData();
            fd.append('file', f);

            try {
                await axios.post('{{ route("upload") }}', fd);
                showMessage('success', `File '${f.name}' uploaded and queued!`);
                fileInput.value = '';
                dropText.textContent = 'Select file / Drag and drop';
                fetchUploads();
            } catch (err) {
                console.error(err);
                showMessage('error', 'Upload failed');
            } finally {
                resetButton();
            }
        });

        function resetButton() {
            uploadButton.disabled = false;
            uploadButton.textContent = 'Upload File';
        }

        // Drag & drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => dropArea.addEventListener(e, ev => {
            ev.preventDefault();
            ev.stopPropagation();
        }));
        ['dragenter', 'dragover'].forEach(e => dropArea.addEventListener(e, () => dropArea.classList.add('hover')));
        ['dragleave', 'drop'].forEach(e => dropArea.addEventListener(e, () => dropArea.classList.remove('hover')));
        dropArea.addEventListener('drop', e => {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                dropText.textContent = `File selected: ${files[0].name}`;
            }
        });
        fileInput.addEventListener('change', e => {
            dropText.textContent = e.target.files.length > 0 ? `File selected: ${e.target.files[0].name}` : 'Select file / Drag and drop';
        });

        fetchUploads(); // Initial load
    </script>
</body>

</html>