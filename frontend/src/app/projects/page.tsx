"use client";

import { useEffect, useState } from "react";
import AppShell from "@/components/layout/AppShell";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Plus, Briefcase, Calendar, DollarSign } from "lucide-react";
import axiosInstance from "@/lib/axios";
import { extractCollection } from "@/lib/api";
import Link from "next/link";

interface Project {
  id: number;
  title: string;
  description: string | null;
  status: string;
  start_date: string | null;
  end_date: string | null;
  budget: string | null;
  client: { name: string };
  tasks: { id: number; status: string }[];
}

const STATUS_COLORS: Record<string, string> = {
  Planning:  "bg-yellow-500/10 text-yellow-500",
  Active:    "bg-green-500/10 text-green-500",
  "On Hold": "bg-orange-500/10 text-orange-500",
  Completed: "bg-blue-500/10 text-blue-500",
  Cancelled: "bg-red-500/10 text-red-500",
};

export default function ProjectsPage() {
  const [projects, setProjects] = useState<Project[]>([]);
  const [loading, setLoading]   = useState(true);

  useEffect(() => {
    axiosInstance.get("/projects")
      .then((r) => setProjects(extractCollection<Project>(r.data)))
      .catch(console.error)
      .finally(() => setLoading(false));
  }, []);

  const completedTasks = (p: Project) => p.tasks.filter((t) => t.status === "Done").length;

  return (
    <AppShell>
      <div className="space-y-6">
        <div className="flex flex-col gap-4 rounded-[1.75rem] border border-emerald-100 bg-white p-5 shadow-[0_12px_30px_rgba(15,23,42,0.04)] lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p className="text-xs font-medium uppercase tracking-[0.18em] text-slate-500">Projects</p>
            <h1 className="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
              Track your active work and milestones.
            </h1>
            <p className="mt-2 text-sm leading-6 text-slate-600 sm:text-base">
              Keep delivery and revenue together in one calm workspace.
            </p>
          </div>
          <Link href="/projects/create">
            <Button size="sm" className="rounded-full bg-emerald-600 text-white hover:bg-emerald-500">
              <Plus className="mr-2 h-4 w-4" />
              New project
            </Button>
          </Link>
        </div>

        {loading ? (
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {[...Array(3)].map((_, i) => (
              <div key={i} className="h-48 rounded-[1.5rem] border border-emerald-100 bg-white shadow-[0_12px_30px_rgba(15,23,42,0.04)] animate-pulse" />
            ))}
          </div>
        ) : projects.length === 0 ? (
          <div className="rounded-[1.5rem] border border-dashed border-emerald-100 bg-white p-12 text-center text-slate-500 shadow-[0_12px_30px_rgba(15,23,42,0.04)]">
            <Briefcase className="mx-auto mb-3 h-10 w-10 opacity-30" />
            <p className="font-medium">No projects yet</p>
            <p className="text-sm mt-1">Create a project to start tracking work.</p>
          </div>
        ) : (
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {projects.map((project) => (
              <Link key={project.id} href={`/projects/${project.id}`}>
                <Card className="h-full cursor-pointer rounded-[1.5rem] border-emerald-100 bg-white shadow-[0_12px_30px_rgba(15,23,42,0.04)] transition-shadow hover:shadow-[0_18px_40px_rgba(15,23,42,0.08)]">
                  <CardHeader className="pb-3">
                    <div className="flex justify-between items-start">
                      <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50">
                        <Briefcase className="h-5 w-5 text-emerald-700" />
                      </div>
                      <span className={`text-xs font-medium px-2.5 py-1 rounded-full ${STATUS_COLORS[project.status] ?? "bg-slate-100 text-slate-600"}`}>
                        {project.status}
                      </span>
                    </div>
                    <CardTitle className="mt-3 text-base text-slate-950">{project.title}</CardTitle>
                    <p className="text-xs text-slate-500">{project.client.name}</p>
                  </CardHeader>
                  <CardContent className="space-y-3">
                    {project.tasks.length > 0 && (
                      <div>
                        <div className="mb-1 flex justify-between text-xs text-slate-500">
                          <span>Tasks</span>
                          <span>{completedTasks(project)}/{project.tasks.length}</span>
                        </div>
                        <div className="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                          <div
                            className="h-full rounded-full bg-slate-950 transition-all"
                            style={{ width: `${(completedTasks(project) / project.tasks.length) * 100}%` }}
                          />
                        </div>
                      </div>
                    )}
                    <div className="flex items-center justify-between border-t border-slate-100 pt-1 text-xs text-slate-500">
                      {project.end_date ? (
                        <span className="flex items-center gap-1">
                          <Calendar className="w-3 h-3" />
                          {new Date(project.end_date).toLocaleDateString()}
                        </span>
                      ) : <span />}
                      {project.budget && (
                        <span className="flex items-center gap-1">
                          <DollarSign className="w-3 h-3" />
                          {Number(project.budget).toLocaleString()}
                        </span>
                      )}
                    </div>
                  </CardContent>
                </Card>
              </Link>
            ))}
          </div>
        )}
      </div>
    </AppShell>
  );
}
