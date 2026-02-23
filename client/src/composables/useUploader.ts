import { ref, computed, type Ref, reactive } from 'vue'
import { formatSize } from '@/utils/formatSize'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

export interface UploadTask {
    name: string
    size: string
    progress: number
    finished: boolean
    error?: boolean
    paused?: boolean
    file: File
}

export function useUploader() {
    const tasks = ref<UploadTask[]>([]);

    const orderedTasks = computed(() =>
        [...tasks.value].sort((a, b) => {
            // Ongoing tasks first (not finished), then completed
            if (a.finished !== b.finished) {
                return a.finished ? 1 : -1;
            }
            return 0; // preserve original order among similar statuses
        })
    );

    async function handleFileInput(event: Event): Promise<any[]> {
        const input = event.target as HTMLInputElement;
        if (!input.files) return [];

        const uploadPromises: Promise<any>[] = [];

        Array.from(input.files).forEach((file) => {
            const task = reactive<UploadTask>({
                name: file.name,
                size: formatSize(file.size),
                progress: 0,
                finished: false,
                file,
            });

            tasks.value.push(task);
            uploadPromises.push(uploadWithXHR(task));
        });

        try {
            const responses = await Promise.all(uploadPromises);
            return responses; // 🎯 Return the server responses
        } catch (error) {
            console.error('Upload error:', error);
            return [];
        }
    }

    async function handleDrop(event: DragEvent): Promise<any[]> {
        const droppedFiles = event.dataTransfer?.files;
        if (!droppedFiles || droppedFiles.length === 0) return [];
        return await handleFileInput(event);
    }

    const completedCount = computed(
        () => tasks.value.filter((t) => t.finished).length
    );

    const totalCount = computed(() => tasks.value.length);


    // async function uploadToStrapi(task: UploadTask) {
    //     const formData = new FormData()
    //     formData.append('files', task.file)

    //     const auth = useAuthStore()
    //     const yourToken = auth.token;

    //     try {
    //         await axios.post('http://localhost:1337/api/upload', formData, {
    //             headers: {
    //                 Authorization: `Bearer ${yourToken}`,
    //                 'Content-Type': 'multipart/form-data'
    //             },
    //             onUploadProgress: (progressEvent) => {
    //                 const total = progressEvent.total ?? 1 // fallback to avoid division by undefined
    //                 const percent = Math.round((progressEvent.loaded * 100) / total)
    //                 task.progress = percent

    //                 console.log('Upload progress:', progressEvent)
    //             }
    //         })
    //         task.finished = true

    //     } catch (error) {
    //         console.error('Upload error:', error)
    //         task.error = true
    //     }
    // }

    function uploadWithXHR(task: UploadTask): Promise<any> {
        const token = useAuthStore().token;
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            console.log('task', task);

            formData.append("files", task.file);

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "http://localhost:8000/api/upload", true);
            xhr.setRequestHeader("Authorization", `Bearer ${token}`);

            xhr.upload.onprogress = (event) => {
                if (event.lengthComputable) {
                    task.progress = Math.round((event.loaded * 100) / event.total);
                }
            };

            xhr.onload = () => {
                task.finished = true;
                try {
                    const rawResponse = JSON.parse(xhr.responseText); // Expecting an array of file objects
                    const enrichedResponse = rawResponse.map((fileRes: any) => ({
                        ...fileRes,
                        original_name: task.name,
                    }));
                    resolve(enrichedResponse); // Tag each file with original filename
                } catch {
                    reject(new Error("Invalid JSON response"));
                }
            };

            xhr.onerror = () => {
                task.error = true;
                reject(new Error("Upload failed"));
            };

            xhr.send(formData);
        });
    }

    return {
        tasks,
        orderedTasks,
        completedCount,
        totalCount,
        handleFileInput,
        handleDrop
    }
}
