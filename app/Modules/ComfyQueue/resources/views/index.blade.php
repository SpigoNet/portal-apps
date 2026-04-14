<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="flex justify-between items-center mb-6 px-4 sm:px-0">
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Gerenciador ComfyUI') }}
                </h2>
                <div class="flex flex-col items-end gap-2">
                    <a href="{{ route('comfy-queue.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">Novo Job</a>
                    <a href="{{ route('comfy-queue.assistant') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md text-sm hover:bg-gray-700">Assistente de Criação</a>
                </div>
            </div>

            <div class="mb-4 px-4 sm:px-0" x-data="jobsFilter()">
                <form @submit.prevent="applyFilter" class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-6">
                        <input
                            type="text"
                            x-model="filters.q"
                            placeholder="Buscar por ID, tipo, prompt_id ou erro"
                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm"
                        >
                    </div>
                    <div class="md:col-span-3">
                        <select x-model="filters.status" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm">
                            <option value="">Todos os status</option>
                            @foreach(['pending' => 'Pending', 'processing' => 'Processing', 'done' => 'Done', 'error' => 'Error'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3 flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm hover:bg-black">Filtrar</button>
                        <button type="button" @click="clearFilter" class="px-4 py-2 border rounded-md text-sm text-gray-600 dark:text-gray-300">Limpar</button>
                    </div>
                </form>
            </div>

            {{-- Success Message --}}
            @if(session('success'))
                <div class="mb-4 px-4 sm:px-0">
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 dark:bg-green-900/30 dark:border-green-500 dark:text-green-300 p-4 rounded">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            {{-- Jobs Grid --}}
            <div class="px-4 sm:px-0" x-data="infiniteScroll()">
                <div id="jobs-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    {{-- Cards carregados via JS --}}
                </div>

                {{-- Loading Indicator --}}
                <div id="loading-indicator" class="flex justify-center py-8" style="display: none;">
                    <div class="flex gap-2">
                        <div class="w-3 h-3 bg-indigo-600 rounded-full animate-bounce"></div>
                        <div class="w-3 h-3 bg-indigo-600 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                        <div class="w-3 h-3 bg-indigo-600 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                    </div>
                </div>

                {{-- Load More Button --}}
                <div id="load-more-btn" class="flex justify-center py-6" style="display: none;">
                    <button @click="loadMore" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Carregar Mais
                    </button>
                </div>

                {{-- No Results --}}
                <div id="no-results" class="flex justify-center py-12" style="display: none;">
                    <p class="text-gray-400">Nenhum job encontrado.</p>
                </div>
            </div>

        </div>
    </div>

    {{-- Modal de Arquivos --}}
    <div id="files-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display: none;">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="sticky top-0 bg-white dark:bg-gray-800 border-b dark:border-gray-700 p-6 flex justify-between items-center">
                <h3 id="modal-title" class="font-semibold text-lg text-gray-800 dark:text-gray-100">Arquivos do Job</h3>
                <button onclick="closeFilesModal()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="files-list" class="p-6 space-y-4">
                {{-- Arquivos carregados dinamicamente --}}
            </div>
        </div>
    </div>

    <script>
        function jobsFilter() {
            return {
                filters: {
                    q: '{{ request("q", "") }}',
                    status: '{{ request("status", "") }}',
                },
                applyFilter() {
                    window.infiniteScroll().reset();
                },
                clearFilter() {
                    this.filters.q = '';
                    this.filters.status = '';
                    this.applyFilter();
                }
            };
        }

        function infiniteScroll() {
            return {
                currentPage: 1,
                isLoading: false,
                hasMore: true,
                
                init() {
                    this.loadJobs();
                    this.observeScroll();
                },

                reset() {
                    this.currentPage = 1;
                    this.hasMore = true;
                    document.getElementById('jobs-grid').innerHTML = '';
                    this.loadJobs();
                },

                getFilters() {
                    const filterEl = document.querySelector('[x-data*="jobsFilter"]');
                    if (filterEl && filterEl.__x) {
                        return {
                            q: filterEl.__x.getUnobservedData().filters.q,
                            status: filterEl.__x.getUnobservedData().filters.status,
                        };
                    }
                    return { q: '', status: '' };
                },

                async loadJobs() {
                    if (this.isLoading || !this.hasMore) return;

                    this.isLoading = true;
                    document.getElementById('loading-indicator').style.display = 'flex';

                    try {
                        const filters = this.getFilters();
                        const params = new URLSearchParams({
                            page: this.currentPage,
                            q: filters.q,
                            status: filters.status,
                        });

                        const response = await fetch(`{{ route('comfy-queue.api.jobs') }}?${params}`);
                        const data = await response.json();

                        if (data.jobs && data.jobs.length > 0) {
                            const grid = document.getElementById('jobs-grid');
                            
                            for (const job of data.jobs) {
                                const card = this.createJobCard(job);
                                grid.appendChild(card);
                            }

                            this.hasMore = data.pagination.has_more;
                            this.currentPage = data.pagination.current_page + 1;

                            if (!this.hasMore) {
                                document.getElementById('load-more-btn').style.display = 'none';
                            } else {
                                document.getElementById('load-more-btn').style.display = 'flex';
                            }

                            document.getElementById('no-results').style.display = 'none';
                        } else if (this.currentPage === 1) {
                            document.getElementById('no-results').style.display = 'flex';
                            document.getElementById('load-more-btn').style.display = 'none';
                        }
                    } catch (error) {
                        console.error('Erro ao carregar jobs:', error);
                    }

                    this.isLoading = false;
                    document.getElementById('loading-indicator').style.display = 'none';
                },

                createJobCard(job) {
                    const card = document.createElement('div');
                    card.className = 'bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow hover:shadow-lg transition-shadow';
                    card.innerHTML = `
                        <div class="relative bg-gray-200 dark:bg-gray-700 aspect-video overflow-hidden flex items-center justify-center">
                            ${this.getMediaPreview(job)}
                            <div class="absolute top-2 right-2">
                                <span class="px-2 py-1 rounded text-xs font-medium ${this.getStatusClass(job.status)}">
                                    ${job.status.charAt(0).toUpperCase() + job.status.slice(1)}
                                </span>
                            </div>
                            ${job.output_files && job.output_files.length > 0 ? `
                                <div class="absolute bottom-2 left-2 bg-black/60 text-white px-2 py-1 rounded text-xs">
                                    ${job.output_files.filter(f => f && f.url).length} arquivo(s)
                                </div>
                            ` : ''}
                        </div>
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-100">Job #${job.id}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">${job.type}</p>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm mb-4">
                                ${job.required_models && job.required_models.length > 0 ? `
                                    <div class="text-gray-600 dark:text-gray-400">
                                        <span class="font-medium">${job.required_models.length} modelo(s)</span>
                                    </div>
                                ` : ''}
                                ${job.error ? `
                                    <div class="text-red-600 dark:text-red-400 truncate" title="${job.error}">
                                        <span class="font-medium">Erro:</span> ${job.error.substring(0, 50)}
                                    </div>
                                ` : ''}
                                ${job.latest_log ? `
                                    <div class="text-gray-600 dark:text-gray-400 truncate" title="${job.latest_log}">
                                        ${job.latest_log.substring(0, 60)}
                                    </div>
                                ` : ''}
                                <div class="text-xs text-gray-500 dark:text-gray-500">
                                    ${job.created_at}${job.finished_at ? ` - ${job.finished_at}` : ''}
                                </div>
                            </div>
                            <div class="flex gap-2 flex-wrap">
                                <a href="/comfy-queue/${job.id}/edit" class="px-2 py-1 rounded border text-xs hover:bg-gray-100 dark:hover:bg-gray-700 transition">Editar</a>
                                ${job.output_files && job.output_files.length > 0 ? `
                                    <button onclick="viewFilesModal(${job.id}, ${JSON.stringify(job.output_files).replace(/"/g, '&quot;')})" class="px-2 py-1 rounded border text-xs text-purple-700 border-purple-300 hover:bg-purple-50 dark:text-purple-300 dark:border-purple-700 dark:hover:bg-purple-900/20 transition">Ver Arquivos</button>
                                ` : ''}
                                <button onclick="duplicateJob(${job.id})" class="px-2 py-1 rounded border text-xs text-indigo-700 border-indigo-300 hover:bg-indigo-50 dark:text-indigo-300 dark:border-indigo-700 dark:hover:bg-indigo-900/20 transition">Duplicar</button>
                                ${['error', 'pending', 'done'].includes(job.status) ? `
                                    <button onclick="requeueJob(${job.id})" class="px-2 py-1 rounded border text-xs text-blue-700 border-blue-300 hover:bg-blue-50 dark:text-blue-300 dark:border-blue-700 dark:hover:bg-blue-900/20 transition">Reenfileirar</button>
                                ` : ''}
                                <button onclick="deleteJob(${job.id})" class="px-2 py-1 rounded border text-xs text-red-700 border-red-300 hover:bg-red-50 dark:text-red-300 dark:border-red-700 dark:hover:bg-red-900/20 transition">Excluir</button>
                            </div>
                        </div>
                    `;
                    return card;
                },

                getMediaPreview(job) {
                    if (!job.output_files || job.output_files.length === 0) {
                        return `
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        `;
                    }

                    for (const file of job.output_files) {
                        if (!file.url) continue;
                        const ext = file.url.split('.').pop().toLowerCase();
                        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                            return `<img src="${file.url}" alt="Job ${job.id}" class="w-full h-full object-cover">`;
                        } else if (['mp4', 'webm', 'mov', 'avi'].includes(ext)) {
                            return `<video src="${file.url}" class="w-full h-full object-cover" controls></video>`;
                        } else if (['mp3', 'wav', 'ogg', 'm4a'].includes(ext)) {
                            return `
                                <div class="flex flex-col items-center justify-center w-full h-full">
                                    <svg class="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M18 3H2c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h14l4 4V5c0-1.1-.9-2-2-2zm-2 8h-4v4h-2v-4H6V9h4V5h2v4h4v2z" />
                                    </svg>
                                    <audio src="${file.url}" controls class="mt-2 w-4/5"></audio>
                                </div>
                            `;
                        }
                    }

                    return `
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    `;
                },

                getStatusClass(status) {
                    const classes = {
                        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                        processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                        done: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                        error: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                    };
                    return classes[status] || '';
                },

                loadMore() {
                    this.loadJobs();
                },

                observeScroll() {
                    window.addEventListener('scroll', () => {
                        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 500) {
                            if (this.hasMore && !this.isLoading) {
                                this.loadJobs();
                            }
                        }
                    });
                }
            };
        }

        function duplicateJob(jobId) {
            if (confirm(`Duplicar job #${jobId}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/comfy-queue/${jobId}/duplicate`;
                form.innerHTML = '@csrf';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function requeueJob(jobId) {
            if (confirm(`Reenfileirar job #${jobId}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/comfy-queue/${jobId}/requeue`;
                form.innerHTML = '@csrf';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteJob(jobId) {
            if (confirm(`Excluir job #${jobId}? Esta ação não pode ser desfeita.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/comfy-queue/${jobId}`;
                form.innerHTML = '@csrf<input type="hidden" name="_method" value="DELETE">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function viewFilesModal(jobId, files) {
            document.getElementById('modal-title').textContent = `Arquivos do Job #${jobId}`;
            const filesList = document.getElementById('files-list');
            filesList.innerHTML = '';

            if (!files || files.length === 0) {
                filesList.innerHTML = '<p class="text-gray-400">Nenhum arquivo encontrado.</p>';
                document.getElementById('files-modal').style.display = 'flex';
                return;
            }

            files.forEach((file, index) => {
                if (!file.url) return;

                const fileItem = document.createElement('div');
                fileItem.className = 'border dark:border-gray-700 rounded-lg p-4 space-y-3';

                const ext = file.url.split('.').pop().toLowerCase();
                const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
                const isVideo = ['mp4', 'webm', 'mov', 'avi'].includes(ext);
                const isAudio = ['mp3', 'wav', 'ogg', 'm4a'].includes(ext);

                let preview = '';
                if (isImage) {
                    preview = `<img src="${file.url}" alt="Arquivo ${index + 1}" class="w-full rounded-lg max-h-96 object-cover">`;
                } else if (isVideo) {
                    preview = `<video src="${file.url}" controls class="w-full rounded-lg max-h-96"></video>`;
                } else if (isAudio) {
                    preview = `<audio src="${file.url}" controls class="w-full"></audio>`;
                } else {
                    preview = '<p class="text-gray-500">Tipo de arquivo não suportado para preview</p>';
                }

                fileItem.innerHTML = `
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-medium text-gray-800 dark:text-gray-200">Arquivo ${index + 1}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">${file.url.split('/').pop()}</p>
                        </div>
                        <a href="${file.url}" target="_blank" download class="px-3 py-1 rounded border text-xs text-indigo-700 border-indigo-300 hover:bg-indigo-50 dark:text-indigo-300 dark:border-indigo-700 dark:hover:bg-indigo-900/20 transition">
                            Download
                        </a>
                    </div>
                    ${preview}
                `;

                filesList.appendChild(fileItem);
            });

            document.getElementById('files-modal').style.display = 'flex';
        }

        function closeFilesModal() {
            document.getElementById('files-modal').style.display = 'none';
        }

        window.addEventListener('click', (e) => {
            const modal = document.getElementById('files-modal');
            if (e.target === modal) {
                closeFilesModal();
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            const scroller = infiniteScroll();
            scroller.init();
        });
    </script>
</x-app-layout>
