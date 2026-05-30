"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import AppShell from "@/components/layout/AppShell";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  DragDropContext,
  Droppable,
  Draggable,
  DropResult,
} from "@hello-pangea/dnd";
import {
  Plus, ArrowLeft, Calendar, Flag, Trash2, Paperclip, Upload,
} from "lucide-react";
import axiosInstance from "@/lib/axios";
import { extractResource } from "@/lib/api";

interface Task {
  id: number;
  title: string;
  description: string | null;
  status: string;
  priority: string;
  due_date: string | null;
  position: number;
}

interface ProjectFile {
  id: number;
  original_name: string;
  size: number | null;
  mime_type: string | null;
  url: string;
  created_at: string;
}

interface Project {
  id: number;
  title: string;
  description: string | null;
  status: string;
  start_date: string | null;
  end_date: string | null;
  budget: string | null;
  client: { name: string };
  tasks: Task[];
  files: ProjectFile[];
}

const COLUMNS = ["Todo", "In Progress", "In Review", "Done"] as const;

const PRIORITY_COLORS: Record<string, string> = {
  Low:    "text-blue-400",
  Medium: "text-yellow-400",
  High:   "text-red-400",
};

export default function ProjectDetailPage() {
  const { id }   = useParams<{ id: string }>();
  const router   = useRouter();
  const [project, setProject]         = useState<Project | null>(null);
  const [tasks, setTasks]             = useState<Task[]>([]);
  const [loading, setLoading]         = useState(true);
  const [addingTo, setAddingTo]       = useState<string | null>(null);
  const [newTaskTitle, setNewTaskTitle] = useState("");
  const [uploading, setUploading]     = useState(false);

  const fetchProject = async () => {
    try {
      const res = await axiosInstance.get(`/projects/${id}`);
      const payload = extractResource<Project>(res.data);
      setProject(payload);
      setTasks(payload.tasks ?? []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    let mounted = true;

    const loadProject = async () => {
      try {
        const res = await axiosInstance.get(`/projects/${id}`);
        const payload = extractResource<Project>(res.data);
        if (mounted) {
          setProject(payload);
          setTasks(payload.tasks ?? []);
        }
      } catch (err) {
        console.error(err);
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    };

    void loadProject();
    return () => {
      mounted = false;
    };
  }, [id]);

  const tasksByColumn = (col: string) =>
    tasks.filter((t) => t.status === col).sort((a, b) => a.position - b.position);

  const handleDragEnd = async (result: DropResult) => {
    const { source, destination, draggableId } = result;
    if (!destination) return;
    if (source.droppableId === destination.droppableId && source.index === destination.index) return;

    const taskId    = parseInt(draggableId);
    const newStatus = destination.droppableId;

    // Optimistic update
    const updated = tasks.map((t) =>
      t.id === taskId ? { ...t, status: newStatus, position: destination.index } : t
    );
    setTasks(updated);

    // Build reorder payload for the destination column
    const colTasks = updated
      .filter((t) => t.status === newStatus)
      .sort((a, b) => a.position - b.position)
      .map((t, i) => ({ id: t.id, status: newStatus, position: i }));

    try {
      await axiosInstance.post(`/projects/${id}/tasks/reorder`, { tasks: colTasks });
    } catch (err) {
      console.error(err);
      fetchProject(); // revert on error
    }
  };

  const addTask = async (status: string) => {
    if (!newTaskTitle.trim()) return;
    try {
      const res = await axiosInstance.post(`/projects/${id}/tasks`, {
        title: newTaskTitle.trim(),
        status,
      });
      setTasks((prev) => [...prev, res.data]);
      setNewTaskTitle("");
      setAddingTo(null);
    } catch (err) {
      console.error(err);
    }
  };

  const deleteTask = async (taskId: number) => {
    try {
      await axiosInstance.delete(`/projects/${id}/tasks/${taskId}`);
      setTasks((prev) => prev.filter((t) => t.id !== taskId));
    } catch (err) {
      console.error(err);
    }
  };

  const handleFileUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files;
    if (!files || files.length === 0) return;
    setUploading(true);
    const formData = new FormData();
    Array.from(files).forEach((f) => formData.append("files[]", f));
    formData.append("project_id", id);
    try {
      await axiosInstance.post("/files", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      fetchProject();
    } catch (err) {
      console.error(err);
    } finally {
      setUploading(false);
      e.target.value = "";
    }
  };

  const deleteFile = async (fileId: number) => {
    try {
      await axiosInstance.delete(`/files/${fileId}`);
      fetchProject();
    } catch (err) {
      console.error(err);
    }
  };

  const formatBytes = (bytes: number | null) => {
    if (!bytes) return "";
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
  };

  if (loading) {
    return (
      <AppShell>
        <div className="space-y-4">
          <div className="h-8 w-48 bg-secondary/40 rounded animate-pulse" />
          <div className="grid grid-cols-4 gap-4">
            {[...Array(4)].map((_, i) => (
              <div key={i} className="h-64 bg-secondary/40 rounded-xl animate-pulse" />
            ))}
          </div>
        </div>
      </AppShell>
    );
  }

  if (!project) {
    return (
      <AppShell>
        <p className="text-muted-foreground">Project not found.</p>
      </AppShell>
    );
  }

  return (
    <AppShell>
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-start gap-4">
          <Button variant="ghost" size="icon" onClick={() => router.back()} className="mt-0.5">
            <ArrowLeft className="w-4 h-4" />
          </Button>
          <div className="flex-1">
            <h1 className="text-2xl font-bold tracking-tight">{project.title}</h1>
            <p className="text-muted-foreground text-sm">{project.client.name}</p>
          </div>
          <span className="text-xs font-medium px-2.5 py-1 rounded-full bg-primary/10 text-primary">
            {project.status}
          </span>
        </div>

        {/* Kanban Board */}
        <div>
          <h2 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-3">
            Kanban Board
          </h2>
          <DragDropContext onDragEnd={handleDragEnd}>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-3 overflow-x-auto pb-2">
              {COLUMNS.map((col) => (
                <div key={col} className="flex flex-col min-w-[200px]">
                  <div className="flex items-center justify-between mb-2 px-1">
                    <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                      {col}
                    </span>
                    <span className="text-xs bg-secondary text-muted-foreground rounded-full px-1.5 py-0.5">
                      {tasksByColumn(col).length}
                    </span>
                  </div>

                  <Droppable droppableId={col}>
                    {(provided, snapshot) => (
                      <div
                        ref={provided.innerRef}
                        {...provided.droppableProps}
                        className={`flex-1 min-h-[120px] rounded-xl p-2 space-y-2 transition-colors ${
                          snapshot.isDraggingOver ? "bg-primary/5 border border-primary/20" : "bg-secondary/30"
                        }`}
                      >
                        {tasksByColumn(col).map((task, index) => (
                          <Draggable key={task.id} draggableId={String(task.id)} index={index}>
                            {(provided, snapshot) => (
                              <div
                                ref={provided.innerRef}
                                {...provided.draggableProps}
                                {...provided.dragHandleProps}
                                className={`bg-card border border-border rounded-lg p-3 text-sm group transition-shadow ${
                                  snapshot.isDragging ? "shadow-lg ring-1 ring-primary/30" : "hover:shadow-sm"
                                }`}
                              >
                                <div className="flex items-start justify-between gap-2">
                                  <p className="font-medium leading-snug flex-1">{task.title}</p>
                                  <button
                                    onClick={() => deleteTask(task.id)}
                                    className="opacity-0 group-hover:opacity-100 text-muted-foreground hover:text-destructive transition-opacity"
                                  >
                                    <Trash2 className="w-3.5 h-3.5" />
                                  </button>
                                </div>
                                <div className="flex items-center gap-2 mt-2">
                                  <Flag className={`w-3 h-3 ${PRIORITY_COLORS[task.priority]}`} />
                                  <span className="text-xs text-muted-foreground">{task.priority}</span>
                                  {task.due_date && (
                                    <span className="flex items-center gap-1 text-xs text-muted-foreground ml-auto">
                                      <Calendar className="w-3 h-3" />
                                      {new Date(task.due_date).toLocaleDateString()}
                                    </span>
                                  )}
                                </div>
                              </div>
                            )}
                          </Draggable>
                        ))}
                        {provided.placeholder}

                        {/* Add task inline */}
                        {addingTo === col ? (
                          <div className="space-y-1.5">
                            <Input
                              autoFocus
                              placeholder="Task title..."
                              value={newTaskTitle}
                              onChange={(e) => setNewTaskTitle(e.target.value)}
                              onKeyDown={(e) => {
                                if (e.key === "Enter") addTask(col);
                                if (e.key === "Escape") { setAddingTo(null); setNewTaskTitle(""); }
                              }}
                              className="h-8 text-sm"
                            />
                            <div className="flex gap-1">
                              <Button size="sm" className="h-7 text-xs flex-1" onClick={() => addTask(col)}>
                                Add
                              </Button>
                              <Button
                                size="sm"
                                variant="ghost"
                                className="h-7 text-xs"
                                onClick={() => { setAddingTo(null); setNewTaskTitle(""); }}
                              >
                                Cancel
                              </Button>
                            </div>
                          </div>
                        ) : (
                          <button
                            onClick={() => setAddingTo(col)}
                            className="w-full flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground py-1.5 px-2 rounded-lg hover:bg-secondary/50 transition-colors"
                          >
                            <Plus className="w-3.5 h-3.5" />
                            Add task
                          </button>
                        )}
                      </div>
                    )}
                  </Droppable>
                </div>
              ))}
            </div>
          </DragDropContext>
        </div>

        {/* Files */}
        <div>
          <div className="flex items-center justify-between mb-3">
            <h2 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider">
              Files
            </h2>
              <label className="cursor-pointer">
              <input
                type="file"
                multiple
                className="hidden"
                onChange={handleFileUpload}
                disabled={uploading}
              />
              <Button size="sm" variant="outline" type="button">
                <Upload className="w-3.5 h-3.5 mr-1.5" />
                {uploading ? "Uploading..." : "Upload"}
              </Button>
            </label>
          </div>

          {project.files.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground border rounded-xl border-dashed text-sm">
              <Paperclip className="w-6 h-6 mx-auto mb-2 opacity-30" />
              No files attached yet.
            </div>
          ) : (
            <div className="space-y-2">
              {project.files.map((file) => (
                <div
                  key={file.id}
                  className="flex items-center justify-between p-3 rounded-lg border border-border bg-card hover:bg-secondary/30 transition-colors"
                >
                  <div className="flex items-center gap-3 min-w-0">
                    <Paperclip className="w-4 h-4 text-muted-foreground shrink-0" />
                    <div className="min-w-0">
                      <p className="text-sm font-medium truncate">{file.original_name}</p>
                      <p className="text-xs text-muted-foreground">
                        {formatBytes(file.size)} · {new Date(file.created_at).toLocaleDateString()}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2 shrink-0">
                    <a
                      href={`${process.env.NEXT_PUBLIC_API_URL}/files/${file.id}/download`}
                      target="_blank"
                      rel="noreferrer"
                      className="text-xs text-primary hover:underline"
                    >
                      Download
                    </a>
                    <button
                      onClick={() => deleteFile(file.id)}
                      className="text-muted-foreground hover:text-destructive transition-colors"
                    >
                      <Trash2 className="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </AppShell>
  );
}
