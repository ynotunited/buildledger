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
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Projects</h1>
            <p className="text-muted-foreground text-sm">Track your active work and milestones.</p>
          </div>
          <Link href="/projects/create">
            <Button size="sm">
              <Plus className="w-4 h-4 mr-2" />
              New Project
            </Button>
          </Link>
        </div>

        {loading ? (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {[...Array(3)].map((_, i) => (
              <div key={i} className="h-48 rounded-xl bg-secondary/40 animate-pulse" />
            ))}
          </div>
        ) : projects.length === 0 ? (
          <div className="text-center p-12 text-muted-foreground border rounded-xl border-dashed">
            <Briefcase className="w-10 h-10 mx-auto mb-3 opacity-30" />
            <p className="font-medium">No projects yet</p>
            <p className="text-sm mt-1">Create a project to start tracking work.</p>
          </div>
        ) : (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {projects.map((project) => (
              <Link key={project.id} href={`/projects/${project.id}`}>
                <Card className="hover:shadow-md transition-shadow cursor-pointer h-full">
                  <CardHeader className="pb-2">
                    <div className="flex justify-between items-start">
                      <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                        <Briefcase className="w-5 h-5 text-primary" />
                      </div>
                      <span className={`text-xs font-medium px-2 py-1 rounded-full ${STATUS_COLORS[project.status] ?? "bg-secondary text-muted-foreground"}`}>
                        {project.status}
                      </span>
                    </div>
                    <CardTitle className="text-base mt-3">{project.title}</CardTitle>
                    <p className="text-xs text-muted-foreground">{project.client.name}</p>
                  </CardHeader>
                  <CardContent className="space-y-3">
                    {project.tasks.length > 0 && (
                      <div>
                        <div className="flex justify-between text-xs text-muted-foreground mb-1">
                          <span>Tasks</span>
                          <span>{completedTasks(project)}/{project.tasks.length}</span>
                        </div>
                        <div className="w-full h-1.5 bg-secondary rounded-full overflow-hidden">
                          <div
                            className="h-full bg-primary rounded-full transition-all"
                            style={{ width: `${(completedTasks(project) / project.tasks.length) * 100}%` }}
                          />
                        </div>
                      </div>
                    )}
                    <div className="flex items-center justify-between text-xs text-muted-foreground pt-1 border-t border-border">
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
